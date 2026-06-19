<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductSearchRequest;
use App\Http\Requests\ProductSearchBomRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;
use App\Models\FgBomDml;
use App\Models\Proj12DmlFgComp;
use App\Models\FgMaterialDml;
use App\Models\MasterMattypeFg;
use App\Models\MasterUOM;
use App\Models\MasterLogisitcSite;
use App\Services\MasterCatLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDO;

class ProductController extends Controller
{
  protected $brands;
  protected $materials;
  protected $masterUom;
  protected $masterSite;
  protected MasterCatLookup $mk;
  protected $mockMaterialStatus;

  public function __construct(MasterCatLookup $mk)
  {
    $this->mk = $mk;
    $this->brands = Brand::all()->map(function ($b) {
      return [
        "abb"  => $b->brand_abb,
        "code" => $b->brand,
      ];
    })->toArray();
    $this->materials = [
      [
        "code"   => "10000001",
        "label"  => "10000001 - Shampoo Fresh 250ml",
        "status" => "ACTIVE"
      ],
      [
        "code"   => "10000002",
        "label"  => "10000002 - Shampoo Fresh 500ml",
        "status" => "ACTIVE"
      ],
      [
        "code"   => "10000003",
        "label"  => "10000003 - Conditioner Smooth 250ml",
        "status" => "HOLD"
      ],
      [
        "code"   => "10000004",
        "label"  => "10000004 - Body Wash Citrus 500ml",
        "status" => "INS"
      ],
      [
        "code"   => "10000005",
        "label"  => "10000005 - Hand Soap Aloe 300ml",
        "status" => "DRAFT"
      ],
      [
        "code"   => "10000006",
        "label"  => "10000006 - Toothpaste Mint 150g",
        "status" => "ACTIVE"
      ],
      [
        "code"   => "10000007",
        "label"  => "10000007 - Laundry Detergent 1L",
        "status" => "BLOCKED"
      ],
      [
        "code"   => "10000008",
        "label"  => "10000008 - Fabric Softener 900ml",
        "status" => "ACTIVE"
      ]
    ];
    $this->mockMaterialStatus = collect($this->materials)
      ->mapWithKeys(fn($item) => [$item['code'] => $item['status']])
      ->toArray();
    $this->masterUom = MasterUOM::all()->map(function ($b) {
      return [
        "value" => $b->code_uom,
        "label" => $b->description_uom,
      ];
    })->toArray();
    $this->masterSite = MasterLogisitcSite::all()->map(function ($b) {
      return [
        "value" => $b->no,
        "label" => $b->site,
      ];
    })->toArray();
  }

  static $mattypes = [[
    "code" => "1",
    "label" => "1",
    "showSite" => true,
    "showBomId" => true
  ], [
    "code" => "5",
    "label" => "5"
  ], [
    "code" => "7",
    "label" => "7"
  ], [
    "code" => "8",
    "label" => "8"
  ], [
    "code" => "9",
    "label" => "9"
  ]];

  protected function fgMattypes(): array
  {
    return [[
      "code" => "1",
      "label" => "1",
      "showSite" => true,
      "showBomId" => true,
    ]];
  }

  protected function fgSubMattypes(string $mattype = '1'): array
  {
    $mattype = (string) $mattype;

    return MasterMattypeFg::query()
      ->selectRaw('TRIM(submattype) as code')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(submattype) IS NOT NULL')
      ->groupByRaw('TRIM(submattype)')
      ->orderByRaw('TRIM(submattype)')
      ->get()
      ->map(function ($row) {
        return [
          'code' => (string) $row->code,
          'label' => (string) $row->code,
        ];
      })
      ->values()
      ->all();
  }

  protected function normalizeSubMattypeOptions($options): array
  {
    if (is_string($options) && $options !== '') {
      $decoded = json_decode($options, true);

      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $options = $decoded;
      }
    }

    return collect($options ?? [])
      ->filter(fn($item) => is_array($item) && array_key_exists('code', $item) && $item['code'] !== null && $item['code'] !== '')
      ->map(function ($item) {
        return [
          'code' => (string) $item['code'],
          'label' => (string) ($item['label'] ?? $item['code']),
        ];
      })
      ->values()
      ->all();
  }

  protected function draftKey(?string $materialId, ?string $bomId = null): ?string
  {
    $key = $materialId ?: $bomId;

    return $key ? "product_drafts.{$key}" : null;
  }

  protected function mergeWithDraft(Request $request, array $inputData): array
  {
    $draftKey = $this->draftKey(
      $request->get('materialId') ?: ($inputData['materialId'] ?? null),
      $request->get('bomId') ?: ($inputData['bomId'] ?? null),
    );

    if (!$draftKey) {
      return $inputData;
    }

    $draft = $request->session()->get($draftKey, []);

    return array_replace($draft, array_filter($inputData, fn($value) => $value !== null));
  }

  protected function persistDraft(Request $request, array $inputData): void
  {
    $draftKey = $this->draftKey($inputData['materialId'] ?? null, $inputData['bomId'] ?? null);

    if ($draftKey) {
      $request->session()->put($draftKey, $inputData);
    }
  }

  protected function fgMaterialToInputData(object|array $row, ?object $bomRow = null): array
  {
    $materialSource = $row instanceof \Illuminate\Database\Eloquent\Model
      ? $row->getAttributes()
      : (array) $row;
    $bomSource = $bomRow instanceof \Illuminate\Database\Eloquent\Model
      ? $bomRow->getAttributes()
      : (array) $bomRow;

    $material = array_change_key_case($materialSource, CASE_LOWER);
    $bom = $bomRow ? array_change_key_case($bomSource, CASE_LOWER) : [];

    return [
      'brand' => trim((string) ($material['brand'] ?? '')),
      'mattype' => trim((string) ($material['mattype'] ?? '')),
      'subMattype' => trim((string) ($material['sub_mattype'] ?? '')),
      'materialId' => trim((string) ($material['material_id_fg_1'] ?? '')),
      'fgStatus' => trim((string) ($material['status'] ?? 'INS')) ?: 'INS',
      'bomId' => trim((string) ($material['fg_bom_id'] ?? $bom['fg_bom_id'] ?? '')),
      'bomDesc' => trim((string) ($material['desc_fg_bom_id'] ?? $bom['desc_fg_bom_id'] ?? '')),
      'finishGoods' => trim((string) ($material['finish_goods'] ?? '')),
      'searchDesc' => trim((string) ($material['search_description'] ?? '')),
      'fullDescEn' => trim((string) ($material['full_description_en'] ?? '')),
      'fullDescTh' => trim((string) ($material['full_description_th'] ?? '')),
      'site' => trim((string) ($material['site'] ?? '')),
      'uom' => trim((string) ($material['uom'] ?? '')),
      'fgComponents' => [],
      'semiFgLv2' => null,
      'semiFgLv1' => null,
      'businessSupply' => null,
    ];
  }

  protected function loadSemiFgLv2ByFgBomId(?string $fgBomId): ?array
  {
    $fgBomId = trim((string) $fgBomId);

    if ($fgBomId === '') {
      return null;
    }

    $row = DB::connection('oracle')
      ->table('PROJ1_2_DML_SEMI_L2_ID')
      ->selectRaw('
        TRIM(FG_BOM_ID) as fg_bom_id,
        TRIM(SEMI_FG_LV2_ID) as semi_fg_lv2_id,
        TRIM(DESC_SEMI_FG_LV2_ID) as desc_semi_fg_lv2_id,
        TRIM(FULL_DESC_SEMI_FG_LV2_EN) as full_desc_semi_fg_lv2_en,
        TRIM(FULL_DESC_SEMI_FG_LV2_TH) as full_desc_semi_fg_lv2_th,
        TRIM(MATTYPE_SEMI_FG_L2ID) as mattype_semi_fg_l2id,
        TRIM(SUB_MATTYPE_SEMI_FG_L2ID) as sub_mattype_semi_fg_l2id,
        TRIM(UOM_SEMI_FG_L2ID) as uom_semi_fg_l2id,
        TRIM(MATERIAL_ID_FG_1) as material_id_fg_1,
        TRIM(SITE) as site,
        TRIM(STATUS_ROW) as status_row
      ')
      ->whereRaw('TRIM(FG_BOM_ID) = ?', [$fgBomId])
      ->first();

    if (!$row) {
      Log::debug('product.load-semi-fg-lv2.not-found', [
        'fgBomId' => $fgBomId,
      ]);
      return null;
    }

    $semiFgLv2 = [
      'id' => trim((string) ($row->semi_fg_lv2_id ?? '')),
      'desc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'searchDesc' => trim((string) ($row->desc_semi_fg_lv2_id ?? '')),
      'fullDescEn' => trim((string) ($row->full_desc_semi_fg_lv2_en ?? '')),
      'fullDescTh' => trim((string) ($row->full_desc_semi_fg_lv2_th ?? '')),
      'uom' => trim((string) ($row->uom_semi_fg_l2id ?? '')),
      'mattype' => trim((string) ($row->mattype_semi_fg_l2id ?? '')),
      'subMattype' => trim((string) ($row->sub_mattype_semi_fg_l2id ?? '')),
      'materialIdFg1' => trim((string) ($row->material_id_fg_1 ?? '')),
      'site' => trim((string) ($row->site ?? '')),
      'components' => [],
      'statusRow' => trim((string) ($row->status_row ?? '')),
    ];

    Log::debug('product.load-semi-fg-lv2.found', [
      'fgBomId' => $fgBomId,
      'semiFgLv2' => $semiFgLv2,
    ]);

    return $semiFgLv2;
  }

  protected function loadSemiFgLv1DraftByFgMaterialId(?string $fgMaterialId): ?array
  {
    $fgMaterialId = trim((string) $fgMaterialId);

    if ($fgMaterialId === '') {
      return null;
    }

    $draft = session()->get("product_drafts.{$fgMaterialId}.semiFgLv1");

    if (!is_array($draft)) {
      return null;
    }

    return [
      'id' => trim((string) ($draft['id'] ?? '')),
      'desc' => trim((string) ($draft['desc'] ?? '')),
      'searchDesc' => trim((string) ($draft['searchDesc'] ?? $draft['desc'] ?? '')),
      'fullDescEn' => trim((string) ($draft['fullDescEn'] ?? '')),
      'fullDescTh' => trim((string) ($draft['fullDescTh'] ?? '')),
      'uom' => trim((string) ($draft['uom'] ?? '')),
      'components' => is_array($draft['components'] ?? null) ? $draft['components'] : [],
    ];
  }

  protected function loadFgComponentsByMaterialId(?string $materialId, ?string $bomId = null): array
  {
    $materialId = trim((string) $materialId);
    $bomId = trim((string) $bomId);

    if ($materialId === '' && $bomId === '') {
      return [];
    }

    $query = Proj12DmlFgComp::query();

    if ($bomId !== '') {
      $query->whereRaw('TRIM(BOM_FG_ID) = ?', [$bomId]);
    } elseif ($materialId !== '') {
      $query->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId]);
    }

    return $query
      ->orderByRaw('TRIM(COMPONENT_ID)')
      ->get()
      ->map(function ($row) {
        $record = $row instanceof \Illuminate\Database\Eloquent\Model
          ? $row->getAttributes()
          : (array) $row;

        $source = array_change_key_case($record, CASE_LOWER);
        $pick = function (array $source, array $keys, string $default = ''): string {
          foreach ($keys as $key) {
            if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
              return trim((string) $source[$key]);
            }
          }

          return $default;
        };

        $code = $pick($source, ['component_id', 'comp_id', 'code']);
        $searchDesc = $pick($source, ['search_desc', 'search_description', 'desc_search']);
        $compDescEn = $pick($source, ['comp_desc_en', 'full_description_en', 'description_en']);
        $compDescTh = $pick($source, ['comp_desc_th', 'full_description_th', 'description_th']);

        return [
          'code' => $code,
          'label' => $searchDesc !== '' ? $searchDesc : ($compDescEn !== '' ? $compDescEn : $code),
          'status' => $pick($source, ['status_row', 'status', 'row_status'], 'INS'),
          'bomId' => $pick($source, ['bom_fg_id', 'fg_bom_id']),
          'searchDesc' => $searchDesc,
          'fullDescEn' => $compDescEn,
          'fullDescTh' => $compDescTh,
          'uom' => $pick($source, ['uom', 'uom_code', 'code_uom']),
          'productCat' => $pick($source, ['product_cat', 'product_category']),
          'productSubCat' => $pick($source, ['prod_sub_cat', 'product_sub_cat']),
        ];
      })
      ->filter(fn ($item) => trim((string) ($item['code'] ?? '')) !== '')
      ->values()
      ->all();
  }

  protected function loadFgMaterialInput(Request $request): ?array
  {
    return $this->loadFgMaterialInputByMaterialId((string) $request->get('materialId', ''));
  }

  protected function loadFgMaterialInputByMaterialId(string $materialId): ?array
  {
    $materialId = trim($materialId);

    Log::debug('product.load-fg-material-input.start', [
      'materialId' => $materialId,
    ]);

    if ($materialId === '') {
      Log::debug('product.load-fg-material-input.skip-empty');
      return null;
    }

    $materialRow = DB::connection('oracle')
      ->table('PROJ1_2_DML_FG_MATTYPE_1')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    if (!$materialRow) {
      Log::debug('product.load-fg-material-input.not-found', [
        'materialId' => $materialId,
      ]);
      return null;
    }

    $bomRow = DB::connection('oracle')
      ->table('PROJ1_2_DML_FG_BOM')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    $inputData = $this->fgMaterialToInputData($materialRow, $bomRow);
    $inputData['fgComponents'] = $this->loadFgComponentsByMaterialId(
      $inputData['materialId'] ?? '',
      $inputData['bomId'] ?? null
    );
    $inputData['semiFgLv2'] = $this->loadSemiFgLv2ByFgBomId($inputData['bomId'] ?? null);
    $inputData['semiFgLv1'] = $this->loadSemiFgLv1DraftByFgMaterialId($inputData['materialId'] ?? null);

    Log::debug('product.load-fg-material-input.found', [
      'materialId' => $materialId,
      'hasBom' => (bool) $bomRow,
      'hasSemiFgLv2' => (bool) $inputData['semiFgLv2'],
      'hasSemiFgLv1' => (bool) $inputData['semiFgLv1'],
      'inputData' => $inputData,
    ]);

    return $inputData;
  }

  protected function assertFgMaterialSaved(string $materialId): array
  {
    $materialId = trim($materialId);
    $rowCount = DB::connection('oracle')
      ->table('PROJ1_2_DML_FG_MATTYPE_1')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->count();

    Log::debug('product.save-matid.post-check', [
      'materialId' => $materialId,
      'rowCount' => $rowCount,
    ]);

    $inputData = $rowCount > 0 ? $this->loadFgMaterialInputByMaterialId($materialId) : null;

    if (!$inputData || trim((string) ($inputData['materialId'] ?? '')) === '') {
      throw ValidationException::withMessages([
        'materialId' => 'Save failed: FG Material was not written to database.',
      ]);
    }

    return $inputData;
  }

  protected function redirectToExistingSearch(Request $request, string $message): RedirectResponse
  {
    $query = array_filter([
      'brand' => trim((string) $request->get('brand', '')),
      'mattype' => trim((string) $request->get('mattype', '')),
      'subMattype' => trim((string) $request->get('subMattype', '')),
    ], fn ($value) => $value !== '');

    return Redirect::route('product.search.bom', array_merge($query, [
      'flashError' => $message,
    ]))->with('error', $message);
  }

  protected function callSaveMatIdProcedure(array $inputData, ?string $userLogin = null, ?string $userRole = null): void
  {
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    $pdo = DB::connection('oracle')->getPdo();
    $error = null;

    Log::debug('product.save-matid.start', [
      'inputData' => $inputData,
      'userLogin' => $userLogin,
      'userRole' => $userRole,
    ]);

    $stmt = $pdo->prepare('BEGIN proj1_2_Save_MATID(:p_mat_id_fg_1, :p_search_desc, :p_full_desc_en, :p_full_desc_th, :p_site, :p_fg_bom_id, :p_desc_fg_bom_id, :p_brand, :p_mattype, :p_sub_mattype, :p_finish_goods, :p_uom, :p_user_role, :p_user_create, :p_user_update, :p_error); END;');
    $stmt->bindValue(':p_mat_id_fg_1', trim((string) ($inputData['materialId'] ?? '')), PDO::PARAM_STR);
    $stmt->bindValue(':p_search_desc', (string) ($inputData['searchDesc'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_full_desc_en', (string) ($inputData['fullDescEn'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_full_desc_th', (string) ($inputData['fullDescTh'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_site', (string) ($inputData['site'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_fg_bom_id', (string) ($inputData['bomId'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_desc_fg_bom_id', (string) ($inputData['bomDesc'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_brand', (string) ($inputData['brand'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', (string) ($inputData['mattype'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', (string) ($inputData['subMattype'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_finish_goods', (string) ($inputData['finishGoods'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_uom', (string) ($inputData['uom'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_user_role', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':p_user_create', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindValue(':p_user_update', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = $this->resolveProcedureErrorMessage($error);
    Log::debug('product.save-matid.finish', [
      'materialId' => $inputData['materialId'] ?? null,
      'error' => $error,
      'resolvedError' => $resolvedError,
    ]);

    if (trim($resolvedError) !== '') {
      Log::warning('product.save-matid.failed', [
        'materialId' => $inputData['materialId'] ?? null,
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);
      throw ValidationException::withMessages([
        'materialId' => $resolvedError ?: 'Unable to save FG Material.',
      ]);
    }
  }

  protected function deleteFgMaterial(string $materialId): void
  {
    $materialId = trim($materialId);

    if ($materialId === '') {
      return;
    }

    FgBomDml::query()
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->delete();

    FgMaterialDml::query()
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->delete();
  }

  protected function buildProductInput(Request $request): array
  {
    $normalizeComponents = function ($items) {
      return collect($items ?? [])
        ->filter(fn($item) => is_array($item) && array_key_exists('code', $item) && $item['code'] !== null && $item['code'] !== '')
        ->keyBy('code')
        ->values()
        ->all();
    };

    $semiFgLv2 = $request->get('semiFgLv2');
    if (is_array($semiFgLv2)) {
      $semiFgLv2['components'] = $normalizeComponents($semiFgLv2['components'] ?? []);
    }

    $semiFgLv1 = $request->get('semiFgLv1');
    if (is_array($semiFgLv1)) {
      $semiFgLv1['components'] = $normalizeComponents($semiFgLv1['components'] ?? []);
    }

    $businessSupply = $request->get('businessSupply');
    if (is_array($businessSupply)) {
      $businessSupply['components'] = $normalizeComponents($businessSupply['components'] ?? []);
    }

    return [
      'brand'          => $request->brand,
      'mattype'        => $request->mattype,
      'subMattype'     => $request->subMattype,
      'materialId'     => $request->materialId,
      'fgStatus'       => $request->fgStatus ?: 'INS',
      'bomId'          => $request->bomId,
      'bomDesc'        => $request->bomDesc,
      'finishGoods'    => $request->finishGoods,
      'fullDescEn'     => $request->fullDescEn,
      'fullDescTh'     => $request->fullDescTh,
      'searchDesc'     => $request->searchDesc,
      'site'           => $request->site,
      'uom'            => $request->uom,
      'fgComponents'   => $normalizeComponents($request->get('fgComponents', [])),
      'semiFgLv2'      => $semiFgLv2,
      'semiFgLv1'      => $semiFgLv1,
      'businessSupply' => $businessSupply,
    ];
  }

  public function new(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = $this->fgMattypes();
    $masterUom = $this->masterUom;
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    return Inertia::render('Product/New', compact('brands', 'mattypes', 'sites', 'masterUom', 'finishGoods'));
  }

  public function view(Request $request): Response|RedirectResponse
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    $masterUom = $this->masterUom;
    $requestedMaterialId = trim((string) $request->get('materialId', ''));
    $InputData = $this->loadFgMaterialInput($request);

    if ($requestedMaterialId !== '' && !$InputData) {
      Log::warning('product.view.material-not-found', [
        'materialId' => $requestedMaterialId,
        'brand' => $request->get('brand'),
        'mattype' => $request->get('mattype'),
        'subMattype' => $request->get('subMattype'),
      ]);

      return $this->redirectToExistingSearch($request, "Material ID {$requestedMaterialId} not found.");
    }

    $InputData = $InputData ?? $this->mergeWithDraft($request, $this->buildProductInput($request));
    Log::debug('product.view', [
      'requestMaterialId' => $request->get('materialId'),
      'resolvedInputData' => $InputData,
    ]);
    return Inertia::render('Product/Detail', compact('InputData', 'brands', 'mattypes', 'sites', 'masterUom', 'finishGoods'));
  }

  public function create(ProductCreateRequest $request): RedirectResponse
  {
    $InputData = $this->buildProductInput($request);
    Log::debug('product.create.start', [
      'inputData' => $InputData,
      'userLogin' => $request->user()?->user_login,
      'userRole' => $request->user()?->role,
    ]);
    $this->callSaveMatIdProcedure($InputData, $request->user()?->user_login, $request->user()?->role);
    $savedInputData = $this->assertFgMaterialSaved((string) ($InputData['materialId'] ?? ''));
    $this->persistDraft($request, $InputData);
    Log::debug('product.create.redirect', [
      'materialId' => $InputData['materialId'] ?? null,
    ]);
    return Redirect::route('product.view', [
      'materialId' => $savedInputData['materialId'] ?? ($InputData['materialId'] ?? null),
    ]);
  }

  public function search(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = $this->fgMattypes();
    $InputData = [
      'brand' => $request->brand,
      'mattype' => $request->mattype,
      'subMattype' => $request->subMattype,
      'subMattypeOptions' => $this->normalizeSubMattypeOptions($request->get('subMattypeOptions', [])),
      'startStep' => $request->get('startStep'),
    ];
    return Inertia::render('Product/Search', compact('brands', 'mattypes', 'InputData'));
  }

  public function searchBom(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = $this->fgMattypes();
    $materials = [];
    $brand = trim((string) $request->get('brand', ''));
    $mattype = trim((string) $request->get('mattype', ''));
    $subMattype = trim((string) $request->get('subMattype', ''));

    if ($brand !== '' && $mattype !== '' && $subMattype !== '') {
      try {
        $materials = FgMaterialDml::query()
          ->whereRaw('TRIM(BRAND) = ?', [$brand])
          ->whereRaw('TRIM(MATTYPE) = ?', [$mattype])
          ->whereRaw('TRIM(SUB_MATTYPE) = ?', [$subMattype])
          ->orderByRaw('TRIM(MATERIAL_ID_FG_1)')
          ->get()
          ->map(function ($row) {
            $materialId = trim((string) ($row->material_id_fg_1 ?? $row->MATERIAL_ID_FG_1 ?? ''));
            $materialDesc = trim((string) ($row->search_description ?? $row->SEARCH_DESCRIPTION ?? ''));

            return [
              'code' => $materialId,
              'label' => trim($materialId . ' - ' . $materialDesc, ' -'),
            ];
          })
          ->values()
          ->all();
      } catch (\Throwable $e) {
        Log::warning('product.search-bom existing materials lookup failed', [
          'brand' => $brand,
          'mattype' => $mattype,
          'subMattype' => $subMattype,
          'error' => $e->getMessage(),
        ]);
      }
    }

    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
      'status'     => '',
    ];
    return Inertia::render('Product/SearchBom', compact('InputData', 'brands', 'mattypes', 'materials'));
  }

  public function subMattypes(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'mattype' => ['required'],
    ]);

    $mattype = (string) $validated['mattype'];
    $query = MasterMattypeFg::query()
      ->selectRaw('TRIM(submattype) as code')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(submattype) IS NOT NULL')
      ->groupByRaw('TRIM(submattype)')
      ->orderByRaw('TRIM(submattype)');

    DB::connection()->enableQueryLog();
    $subMattypes = $query
      ->get()
      ->map(function ($row) {
        return [
          'code' => (string) $row->code,
          'label' => (string) $row->code,
        ];
      })
      ->values()
      ->all();
    $queryLog = DB::getQueryLog();

    Log::debug('product.sub-mattypes', [
      'mattype' => $mattype,
      'sql' => $query->toSql(),
      'bindings' => $query->getBindings(),
      'query_log' => $queryLog,
      'count' => count($subMattypes),
    ]);

    if ($request->boolean('debug')) {
      return response()->json([
        'mattype' => $mattype,
        'subMattypes' => $subMattypes,
        'debug' => [
          'sql' => $query->toSql(),
          'bindings' => $query->getBindings(),
          'queryLog' => $queryLog,
          'count' => count($subMattypes),
        ],
      ]);
    }

    return response()->json([
      'mattype' => $mattype,
      'subMattypes' => $subMattypes,
    ]);
  }

  public function edit(Request $request): Response|RedirectResponse
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    $masterUom = $this->masterUom;
    $requestedMaterialId = trim((string) $request->get('materialId', ''));
    $InputData = $this->loadFgMaterialInput($request);

    if ($requestedMaterialId !== '' && !$InputData) {
      Log::warning('product.edit.material-not-found', [
        'materialId' => $requestedMaterialId,
        'brand' => $request->get('brand'),
        'mattype' => $request->get('mattype'),
        'subMattype' => $request->get('subMattype'),
      ]);

      return $this->redirectToExistingSearch($request, "Material ID {$requestedMaterialId} not found.");
    }

    $InputData = $InputData ?? $this->mergeWithDraft($request, $this->buildProductInput($request));
    $isDisabled = false;
    $isEditMode = true;
    return Inertia::render('Product/Detail', compact('InputData', 'brands', 'mattypes', 'sites', 'masterUom', 'finishGoods', 'isDisabled', 'isEditMode'));
  }

  public function find(ProductSearchRequest $request): RedirectResponse
  {
    return Redirect::route('product.search.bom', [
      'brand' => $request->brand,
      'mattype' => $request->mattype,
      'subMattype' => $request->subMattype,
    ]);
  }

  public function findBom(ProductSearchBomRequest $request): RedirectResponse
  {
    $InputData = $this->loadFgMaterialInput($request) ?? [
      'brand' => $request->brand,
      'mattype' => $request->mattype,
      'subMattype' => $request->subMattype,
      'materialId' => $request->materialId,
    ];
    return Redirect::route('product.view', $InputData);
  }

  public function materialStatus(Request $request): JsonResponse
  {
    $materialId = $request->get('materialId');

    if (!$materialId) {
      return response()->json([
        'materialId' => null,
        'status' => null,
        'mock' => true,
      ]);
    }

    $status = $this->mockMaterialStatus[$materialId] ?? 'UNKNOWN';

    return response()->json([
      'materialId' => $materialId,
      'status' => $status,
      'mock' => true,
    ]);
  }

  public function generateMaterialId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'brand'      => ['required'],
      'mattype'    => ['required'],
      'subMattype' => ['required'],
    ]);

    $materialId = null;
    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_MATID(:p_brand, :p_mattype, :p_sub_mattype, :p_suggest_material_id, :p_error); END;');
    $stmt->bindParam(':p_brand', $validated['brand'], PDO::PARAM_STR);
    $stmt->bindParam(':p_mattype', $validated['mattype'], PDO::PARAM_STR);
    $stmt->bindParam(':p_sub_mattype', $validated['subMattype'], PDO::PARAM_STR);
    $stmt->bindParam(':p_suggest_material_id', $materialId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_MATID',
      'materialId' => $materialId,
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }

  public function generateBomId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'suggestId' => ['required'],
      'site' => ['required'],
    ]);

    $bomId = null;
    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_BOMID_FG(:p_suggest_id, :p_site, :p_out_bomid, :p_error); END;');
    $stmt->bindParam(':p_suggest_id', $validated['suggestId'], PDO::PARAM_STR);
    $stmt->bindParam(':p_site', $validated['site'], PDO::PARAM_STR);
    $stmt->bindParam(':p_out_bomid', $bomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_BOMID_FG',
      'bomId' => $bomId,
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }

  public function update(ProductCreateRequest $request): RedirectResponse
  {
    $InputData = $this->buildProductInput($request);
    Log::debug('product.update.start', [
      'inputData' => $InputData,
      'userLogin' => $request->user()?->user_login,
      'userRole' => $request->user()?->role,
    ]);
    $this->callSaveMatIdProcedure($InputData, $request->user()?->user_login, $request->user()?->role);
    $savedInputData = $this->assertFgMaterialSaved((string) ($InputData['materialId'] ?? ''));
    $this->persistDraft($request, $InputData);
    Log::debug('product.update.redirect', [
      'materialId' => $savedInputData['materialId'] ?? ($InputData['materialId'] ?? null),
    ]);
    return Redirect::route('product.view', [
      'materialId' => $savedInputData['materialId'] ?? ($InputData['materialId'] ?? null),
    ]);
  }

  public function delete(Request $request): RedirectResponse
  {
    $this->deleteFgMaterial((string) $request->get('materialId'));
    $draftKey = $this->draftKey($request->get('materialId'), $request->get('bomId'));
    if ($draftKey) {
      $request->session()->forget($draftKey);
    }

    return Redirect::route('product.search');
  }
}
