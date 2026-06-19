<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PackMaterialCreateRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;
use App\Models\MasterMattypePack;
use App\Models\MasterUOM;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDO;

class PackMaterialController extends Controller
{
  protected $brands;
  protected $materials;
  protected $uoms;
  protected $productSubCategories;
  protected $packSubMattypesList;

  public function __construct()
  {
    $this->brands = Brand::all()->map(function ($b) {
      return [
        "abb"  => $b->brand_abb,
        "code" => $b->brand,
      ];
    })->toArray();
    $this->materials = [
      [
        "code"  => "001",
        "label" => "001"
      ], [
        "code"  => "002",
        "label" => "002"
      ]
    ];
    $this->uoms = MasterUOM::all()->map(function ($b) {
      return [
        "value" => $b->code_uom,
        "label" => $b->description_uom,
      ];
    })->toArray();
    $this->packSubMattypesList = $this->packSubMattypes();
    $this->productSubCategories = $this->packProductSubCategories();
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

  static $sites = [[
    "code" => "RJ",
    "label" => "RJ"
  ], [
    "code" => "RK",
    "label" => "RK"
  ], [
    "code" => "SN",
    "label" => "SN"
  ]];

  protected function persistFgDraft(Request $request, array $fgDetail): void
  {
    $key = $fgDetail['materialId'] ?? $request->get('fgMaterialId') ?? $fgDetail['bomId'] ?? $request->get('fgBomId');

    if ($key) {
      $request->session()->put("product_drafts.{$key}", $fgDetail);
    }
  }

  protected function upsertComponent(array $components, array $componentItem): array
  {
    $collection = collect($components);
    $existingIndex = $collection->search(fn($item) => ($item['code'] ?? null) === $componentItem['code']);

    if ($existingIndex === false) {
      $collection->push($componentItem);
    } else {
      $collection->put($existingIndex, array_merge($collection->get($existingIndex), $componentItem));
    }

    return $collection->values()->all();
  }

  protected function resolveFgSiteByMaterialId(?string $materialId): string
  {
    $materialId = trim((string) $materialId);

    if ($materialId === '') {
      return '';
    }

    try {
      $row = DB::connection('oracle')
        ->table('PROJ1_2_DML_FG_MATTYPE_1')
        ->selectRaw('TRIM(SITE) as site')
        ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
        ->first();

      return trim((string) ($row->site ?? ''));
    } catch (\Throwable $e) {
      Log::debug('packmaterial.resolve-fg-site.failed', [
        'materialId' => $materialId,
        'error' => $e->getMessage(),
      ]);

      return '';
    }
  }

  protected function loadFgMaterialInputByMaterialId(string $materialId): ?array
  {
    $materialId = trim($materialId);

    if ($materialId === '') {
      return null;
    }

    $materialRow = DB::connection('oracle')
      ->table('PROJ1_2_DML_FG_MATTYPE_1')
      ->whereRaw('TRIM(MATERIAL_ID_FG_1) = ?', [$materialId])
      ->first();

    if (!$materialRow) {
      return null;
    }

    $material = $materialRow instanceof \Illuminate\Database\Eloquent\Model
      ? $materialRow->getAttributes()
      : (array) $materialRow;

    $materialSource = array_change_key_case($material, CASE_LOWER);

    $pick = function (array $source, array $keys, string $default = ''): string {
      foreach ($keys as $key) {
        if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
          return trim((string) $source[$key]);
        }
      }

      return $default;
    };

    return [
      'materialId' => $materialId,
      'bomId' => $pick($materialSource, ['fg_bom_id', 'bom_fg_id', 'bom_id']),
      'bomDesc' => $pick($materialSource, ['desc_fg_bom_id', 'bom_fg_desc', 'desc_bom_id']),
    ];
  }

  protected function generateFgBomId(?string $suggestId, ?string $site): array
  {
    $suggestId = trim((string) $suggestId);
    $site = trim((string) $site);

    if ($suggestId === '' || $site === '') {
      return [
        'bomId' => '',
        'error' => '',
      ];
    }

    $pdo = DB::connection('oracle')->getPdo();
    $bomId = null;
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_BOMID_FG(:p_suggest_id, :p_site, :p_out_bomid, :P_ERROR); END;');
    $stmt->bindValue(':p_suggest_id', $suggestId, PDO::PARAM_STR);
    $stmt->bindValue(':p_site', $site, PDO::PARAM_STR);
    $stmt->bindParam(':p_out_bomid', $bomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return [
      'bomId' => trim((string) $bomId),
      'error' => trim((string) $this->resolveProcedureErrorMessage($error)),
    ];
  }

  protected function generateSemiFgLv2BomId(?string $fgMaterialId, ?string $fgBomId, ?string $mattype, ?string $subMattype): array
  {
    $fgMaterialId = trim((string) $fgMaterialId);
    $fgBomId = trim((string) $fgBomId);
    $mattype = trim((string) ($mattype ?: '2'));
    $subMattype = trim((string) ($subMattype ?: '0'));

    if ($fgMaterialId === '' || $fgBomId === '') {
      return [
        'bomId' => '',
        'levelMaterialId' => '',
        'error' => '',
      ];
    }

    $pdo = DB::connection('oracle')->getPdo();
    $semiFgLv2BomId = null;
    $semiFgLv2Id = null;
    $error = null;
    $stmt = $pdo->prepare('BEGIN proj1_2_gen_semifg_lv2(:p_fg_matid, :p_fg_bomid, :p_mattype, :p_sub_mattype, :p_semifg_lv2_bomid, :p_semifg_lv2_id, :P_ERROR); END;');
    $stmt->bindValue(':p_fg_matid', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindValue(':p_fg_bomid', $fgBomId, PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', $subMattype, PDO::PARAM_STR);
    $stmt->bindParam(':p_semifg_lv2_bomid', $semiFgLv2BomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_semifg_lv2_id', $semiFgLv2Id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return [
      'bomId' => trim((string) $semiFgLv2BomId),
      'levelMaterialId' => trim((string) $semiFgLv2Id),
      'error' => trim((string) $this->resolveProcedureErrorMessage($error)),
    ];
  }

  protected function saveFgComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): void
  {
    $userLogin = $userLogin ?: 'system';
    $userRole = $userRole ?: 'GTIN';
    $bomFgId = trim((string) ($data['bomId'] ?? ''));
    $mattype = trim((string) ($data['mattype'] ?? '5'));
    $subMattype = trim((string) ($data['subMattype'] ?? ''));
    $productCat = trim((string) ($data['productCat'] ?? ''));
    $productSubCat = trim((string) ($data['productSubCat'] ?? ''));
    $componentId = trim((string) ($data['componentId'] ?? ''));
    $searchDesc = trim((string) ($data['searchDesc'] ?? ''));
    $fullDescEn = trim((string) ($data['fullDescEn'] ?? ''));
    $fullDescTh = trim((string) ($data['fullDescTh'] ?? ''));
    $uom = trim((string) ($data['uom'] ?? ''));

    if ($bomFgId === '' || $componentId === '') {
      throw ValidationException::withMessages([
        'componentId' => 'BOM ID or Component ID is missing.',
      ]);
    }

    $pdo = DB::connection('oracle')->getPdo();
    $error = null;
    $stmt = $pdo->prepare('BEGIN PROJ1_2_SAVE_COMP_BOMFG(:P_BOM_FG_ID, :P_MATTYPE, :P_SUBMATTYPE, :P_PRODUCT_CAT, :P_PROD_SUB_CAT, :P_COMPONENT_ID, :P_SEARCH_DESC, :P_COMP_DESC_EN, :P_COMP_DESC_TH, :P_UOM, :P_USER_ROLE, :P_USER, :P_ERROR); END;');
    $stmt->bindValue(':P_BOM_FG_ID', $bomFgId, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $mattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUBMATTYPE', $subMattype, PDO::PARAM_STR);
    $stmt->bindValue(':P_PRODUCT_CAT', $productCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_PROD_SUB_CAT', $productSubCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMPONENT_ID', $componentId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_EN', $fullDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_TH', $fullDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', (string) $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER', (string) $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    $resolvedError = trim((string) $this->resolveProcedureErrorMessage($error));
    if ($resolvedError !== '') {
      Log::warning('packmaterial.save-fg-component-bom.failed', [
        'bomFgId' => $bomFgId,
        'componentId' => $componentId,
        'error' => $error,
        'resolvedError' => $resolvedError,
      ]);

      throw ValidationException::withMessages([
        'componentId' => $resolvedError,
      ]);
    }
  }

  protected function loadFgComponentByMaterialAndComponentId(?string $materialId, ?string $componentId): ?array
  {
    $materialId = trim((string) $materialId);
    $componentId = trim((string) $componentId);

    if ($materialId === '' || $componentId === '') {
      return null;
    }

    $fgInput = $this->loadFgMaterialInputByMaterialId($materialId);
    $bomId = trim((string) ($fgInput['bomId'] ?? ''));

    if ($bomId === '') {
      return null;
    }

    $row = DB::connection('oracle')
      ->table('proj1_2_dml_fg_comp')
      ->whereRaw('TRIM(BOM_FG_ID) = ?', [$bomId])
      ->whereRaw('TRIM(COMPONENT_ID) = ?', [$componentId])
      ->first();

    if (!$row) {
      return null;
    }

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

    return [
      'bomId' => $pick($source, ['bom_fg_id', 'fg_bom_id']),
      'bomDesc' => $pick($source, ['desc_fg_bom_id', 'bom_fg_desc', 'fg_bom_desc']),
      'mattype' => $pick($source, ['mattype'], '5'),
      'subMattype' => $pick($source, ['submattype', 'sub_mattype']),
      'productCat' => $pick($source, ['product_cat', 'product_category']),
      'productSubCat' => $pick($source, ['prod_sub_cat', 'product_sub_cat']),
      'componentId' => $pick($source, ['component_id', 'comp_id'], $componentId),
      'searchDesc' => $pick($source, ['search_desc', 'search_description']),
      'fullDescEn' => $pick($source, ['comp_desc_en', 'full_desc_en', 'full_description_en']),
      'fullDescTh' => $pick($source, ['comp_desc_th', 'full_desc_th', 'full_description_th']),
      'uom' => $pick($source, ['uom', 'uom_code', 'code_uom']),
      'materialId' => $materialId,
    ];
  }

  protected function packSubMattypes(string $mattype = '5'): array
  {
    $mattype = (string) $mattype;

    return MasterMattypePack::query()
      ->selectRaw('TRIM(submattype) as code, TRIM(description) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(submattype) IS NOT NULL')
      ->whereRaw('TRIM(product_sub_cat) IS NULL')
      ->groupByRaw('TRIM(submattype), TRIM(description)')
      ->orderByRaw('TRIM(submattype)')
      ->get()
      ->map(function ($row) {
        return [
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function packProductCategories(string $mattype = '5', ?string $subMattype = null): array
  {
    $mattype = (string) $mattype;

    $query = MasterMattypePack::query()
      ->selectRaw('TRIM(SUBMATTYPE) as subMattypeCode, TRIM(PRODUCT_CATEGORY) as code, MIN(TRIM(DESCRIPTION)) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(PRODUCT_CATEGORY) IS NOT NULL')
      ->whereRaw('TRIM(PRODUCT_SUB_CAT) IS NOT NULL')
      ->groupByRaw('TRIM(SUBMATTYPE), TRIM(PRODUCT_CATEGORY)')
      ->orderByRaw('TRIM(SUBMATTYPE), TRIM(PRODUCT_CATEGORY)');

    if ($subMattype !== null && $subMattype !== '') {
      $query->whereRaw('TRIM(SUBMATTYPE) = ?', [(string) $subMattype]);
    } else {
      return [];
    }

    return $query
      ->get()
      ->map(function ($row) {
        return [
          'subMattypeCode' => (string) $row->subMattypeCode,
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function packProductSubCategories(string $mattype = '5', ?string $subMattype = null, ?string $productCat = null): array
  {
    $mattype = (string) $mattype;

    $query = MasterMattypePack::query()
      ->selectRaw('TRIM(SUBMATTYPE) as subMattypeCode, TRIM(product_category) as productCatCode, TRIM(product_sub_cat) as code, TRIM(description) as description')
      ->whereRaw('TRIM(mattype) = ?', [$mattype])
      ->whereRaw('TRIM(product_category) IS NOT NULL')
      ->whereRaw('TRIM(product_sub_cat) IS NOT NULL')
      ->groupByRaw('TRIM(SUBMATTYPE), TRIM(product_category), TRIM(product_sub_cat), TRIM(description)')
      ->orderByRaw('TRIM(SUBMATTYPE), TRIM(product_category), TRIM(product_sub_cat)');

    if ($subMattype !== null && $subMattype !== '') {
      $query->whereRaw('TRIM(SUBMATTYPE) = ?', [(string) $subMattype]);
    }

    if ($productCat !== null && $productCat !== '') {
      $query->whereRaw('TRIM(product_category) = ?', [(string) $productCat]);
    }

    return $query
      ->get()
      ->map(function ($row) {
        return [
          'subMattypeCode' => (string) $row->subMattypeCode,
          'productCatCode' => (string) $row->productCatCode,
          'code' => (string) $row->code,
          'description' => (string) $row->description,
        ];
      })
      ->values()
      ->toArray();
  }

  protected function redirectToOwner(Request $request, array $components): RedirectResponse
  {
    $ownerLevel = $request->get('ownerLevel', 'fg');
    $fgDetail = $request->get('fgDetail', []);
    $ownerDetail = $request->get('ownerDetail', []);

    if ($ownerLevel === 'fg') {
      $fgDetail['fgComponents'] = $components;
      $this->persistFgDraft($request, $fgDetail);
      return Redirect::route('product.view', $fgDetail);
    }

    if ($ownerLevel === 'semiFgLv1') {
      $fgDetail['semiFgLv1'] = array_merge($fgDetail['semiFgLv1'] ?? [], $ownerDetail, ['components' => $components]);
      $this->persistFgDraft($request, $fgDetail);
      return Redirect::route('material-levels.semi-fg-lv1.new', [
        'mode' => 'view',
        'fgDetail' => $fgDetail,
        'materialId' => $fgDetail['materialId'] ?? null,
        'bomId' => $fgDetail['bomId'] ?? null,
        'bomDesc' => $fgDetail['bomDesc'] ?? null,
        'levelMaterialId' => $ownerDetail['id'] ?? null,
        'searchDesc' => $ownerDetail['searchDesc'] ?? null,
        'fullDescEn' => $ownerDetail['fullDescEn'] ?? null,
        'fullDescTh' => $ownerDetail['fullDescTh'] ?? null,
        'uom' => $ownerDetail['uom'] ?? null,
        'components' => $components,
      ]);
    }

    if ($ownerLevel === 'businessSupply') {
      $fgDetail['businessSupply'] = array_merge($fgDetail['businessSupply'] ?? [], $ownerDetail, ['components' => $components]);
      $this->persistFgDraft($request, $fgDetail);
      $backRoute = $request->get('backRoute', 'business-supply.new');
      return Redirect::route($backRoute, [
        'mode' => 'view',
        'fgDetail' => $fgDetail,
        'materialId' => $fgDetail['materialId'] ?? null,
        'bomId' => $fgDetail['bomId'] ?? null,
        'bomDesc' => $fgDetail['bomDesc'] ?? null,
        'levelMaterialId' => $ownerDetail['id'] ?? null,
        'searchDesc' => $ownerDetail['searchDesc'] ?? null,
        'fullDescEn' => $ownerDetail['fullDescEn'] ?? null,
        'fullDescTh' => $ownerDetail['fullDescTh'] ?? null,
        'uom' => $ownerDetail['uom'] ?? null,
        'components' => $components,
        'bizsupId' => $ownerDetail['id'] ?? null,
      ]);
    }

    $fgDetail['semiFgLv2'] = array_merge($fgDetail['semiFgLv2'] ?? [], $ownerDetail, ['components' => $components]);
    $this->persistFgDraft($request, $fgDetail);
    return Redirect::route('material-levels.semi-fg-lv2.new', [
      'mode' => 'view',
      'fgDetail' => $fgDetail,
      'materialId' => $fgDetail['materialId'] ?? null,
      'bomId' => $fgDetail['bomId'] ?? null,
      'bomDesc' => $fgDetail['bomDesc'] ?? null,
      'levelMaterialId' => $ownerDetail['id'] ?? null,
      'searchDesc' => $ownerDetail['searchDesc'] ?? null,
      'fullDescEn' => $ownerDetail['fullDescEn'] ?? null,
      'fullDescTh' => $ownerDetail['fullDescTh'] ?? null,
      'uom' => $ownerDetail['uom'] ?? null,
      'components' => $components,
    ]);
  }

  public function new(Request $request): Response
  {
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] == '5'));
    $subMattypes = $this->packSubMattypesList;
    $referentMaterialId = trim((string) ($request->referentMaterialId ?: $request->materialId ?: $request->fgMaterialId ?: ''));
    $actionMode = $request->actionMode ?: 'create';
    $ownerLevel = $request->ownerLevel ?: 'fg';
    $generatedBomId = trim((string) $request->bomId);
    $componentId = trim((string) $request->componentId);
    $loadedComponent = null;

    if ($ownerLevel === 'fg' && in_array($actionMode, ['edit', 'delete'], true) && $referentMaterialId !== '' && $componentId !== '') {
      $loadedComponent = $this->loadFgComponentByMaterialAndComponentId($referentMaterialId, $componentId);
    }

    if (in_array($ownerLevel, ['fg', 'semiFgLv2'], true) && $actionMode === 'create' && $generatedBomId === '') {
      if ($ownerLevel === 'semiFgLv2') {
        $fgMaterialId = trim((string) ($request->fgMaterialId ?: $referentMaterialId));
        $fgBomId = trim((string) ($request->fgBomId ?: $request->bomId));
        if ($fgBomId === '' && $fgMaterialId !== '') {
          $fgInput = $this->loadFgMaterialInputByMaterialId($fgMaterialId);
          $fgBomId = trim((string) ($fgInput['bomId'] ?? ''));
        }

        $generated = $this->generateSemiFgLv2BomId(
          $fgMaterialId,
          $fgBomId,
          $request->mattype ?: '2',
          $request->subMattype ?: '0'
        );

        if ($generated['error'] !== '') {
          Log::warning('packmaterial.generate-semi-fg-lv2-bom-id.failed', [
            'fgMaterialId' => $fgMaterialId,
            'fgBomId' => $fgBomId,
            'error' => $generated['error'],
          ]);
        }

        $generatedBomId = $generated['bomId'];
        if ($generated['levelMaterialId'] !== '') {
          $request = $request->merge([
            'levelMaterialId' => $generated['levelMaterialId'],
          ]);
        }
      } else {
      $site = trim((string) ($request->site ?: $this->resolveFgSiteByMaterialId($referentMaterialId)));
      $generated = $this->generateFgBomId($referentMaterialId, $site);

      if ($generated['error'] !== '') {
        Log::warning('packmaterial.generate-fg-bom-id.failed', [
          'referentMaterialId' => $referentMaterialId,
          'site' => $site,
          'error' => $generated['error'],
        ]);
      }

      $generatedBomId = $generated['bomId'];
      }
    }

    $InputData = [
      "mattype"    => "5",
      "subMattype" => $loadedComponent['subMattype'] ?? ($request->subMattype ?? ''),
      'actionMode' => $actionMode,
      'ownerLevel' => $ownerLevel,
      'backRoute' => $request->backRoute ?: 'product.view',
      'backMaterialId' => $request->backMaterialId ?: $request->referentMaterialId ?: $request->materialId ?: $request->fgMaterialId,
      'referentMaterialId' => $referentMaterialId,
      'materialId' => $referentMaterialId,
      'fgMaterialId' => $referentMaterialId,
      'bomId'      => $loadedComponent['bomId'] ?? $generatedBomId,
      'bomDesc'    => $loadedComponent['bomDesc'] ?? $request->bomDesc,
      'fgDetail'   => $request->get('fgDetail', []),
      'ownerDetail' => $request->get('ownerDetail', []),
      'components' => $request->get('components', []),
      'fgMaterialId' => $request->fgMaterialId ?: $referentMaterialId,
      'fgBomId'    => $request->fgBomId,
      'fgBomDesc'  => $request->fgBomDesc,
      'levelMaterialId' => $request->levelMaterialId ?: $request->get('materialId'),
      'levelSearchDesc' => $request->levelSearchDesc,
      'levelFullDescEn' => $request->levelFullDescEn,
      'levelFullDescTh' => $request->levelFullDescTh,
      'levelUom'   => $request->levelUom,
      'productCat' => $loadedComponent['productCat'] ?? $request->productCat,
      'productSubCat' => $loadedComponent['productSubCat'] ?? $request->productSubCat,
      'componentId' => $loadedComponent['componentId'] ?? $componentId,
      'searchDesc' => $loadedComponent['searchDesc'] ?? $request->searchDesc,
      'fullDescEn' => $loadedComponent['fullDescEn'] ?? $request->fullDescEn,
      'fullDescTh' => $loadedComponent['fullDescTh'] ?? $request->fullDescTh,
      'uom'       => $loadedComponent['uom'] ?? $request->uom,
    ];

    $uoms = $this->uoms;
    $productCategories = [];
    $productSubCategories = $this->productSubCategories;
    return Inertia::render('PackMaterial/New', compact('InputData', 'mattypes', 'subMattypes', 'uoms', 'productCategories', 'productSubCategories'));
  }

  public function productCategories(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'subMattype' => ['nullable'],
    ]);

    $subMattype = $validated['subMattype'] ?? null;

    if ($subMattype === null || $subMattype === '') {
      return response()->json([
        'subMattype' => '',
        'productCategories' => [],
        'productSubCategories' => [],
      ]);
    }

    $productCategories = $this->packProductCategories('5', (string) $subMattype);
    $productCat = $productCategories[0]['code'] ?? '';

    return response()->json([
      'subMattype' => (string) $subMattype,
      'productCategories' => $productCategories,
      'productSubCategories' => $this->packProductSubCategories('5', (string) $subMattype, $productCat),
      'productCat' => $productCat,
    ]);
  }

  public function create(PackMaterialCreateRequest $request): RedirectResponse
  {
    return $this->saveComponentForOwner($request);
  }

  public function update(PackMaterialCreateRequest $request): RedirectResponse
  {
    return $this->saveComponentForOwner($request);
  }

  protected function saveComponentForOwner(PackMaterialCreateRequest $request): RedirectResponse
  {
    $componentId = $request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    );

    if (in_array($request->get('ownerLevel', 'fg'), ['fg', 'semiFgLv2'], true)) {
      $this->saveFgComponentBom(array_merge($request->all(), [
        'componentId' => $componentId,
      ]), $request->user()?->user_login, $request->user()?->role);

      if ($request->get('ownerLevel', 'fg') === 'semiFgLv2') {
        return Redirect::route($request->get('backRoute', 'material-levels.semi-fg-lv2.new'), [
          'referentMaterialId' => $request->get('referentMaterialId') ?: $request->get('fgMaterialId') ?: $request->get('materialId'),
          'mode' => 'view',
          'levelMaterialId' => $request->get('levelMaterialId') ?: $request->get('materialId'),
        ]);
      }

      return Redirect::route($request->get('backRoute', 'product.view'), [
        'materialId' => $request->get('backMaterialId')
          ?: $request->get('referentMaterialId')
          ?: $request->get('materialId')
          ?: $request->get('fgMaterialId'),
      ]);
    }

    $componentItem = [
      'code' => $componentId,
      'label' => $request->searchDesc ?: $request->fullDescEn ?: $componentId,
      'status' => 'INS',
      'searchDesc' => $request->searchDesc,
      'fullDescEn' => $request->fullDescEn,
      'fullDescTh' => $request->fullDescTh,
      'uom' => $request->uom,
      'productCat' => $request->productCat,
      'productSubCat' => $request->productSubCat,
    ];

    $components = $this->upsertComponent($request->get('components', []), $componentItem);

    return $this->redirectToOwner($request, $components);
  }

  public function callNew(Request $request): RedirectResponse
  {
    $InputData = [
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
      'subMattype' => $request->subMattype,
    ];
    return Redirect::route('packmaterial.new', $InputData);
  }

  public function generateComponentId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'productSubCat' => ['required'],
    ]);

    $componentId = null;
    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_COMP_BOMFG(:p_prd_sub_cat, :p_componenid, :p_error); END;');
    $stmt->bindParam(':p_prd_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
    $stmt->bindParam(':p_componenid', $componentId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_COMP_BOMFG',
      'componentId' => $componentId,
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }
}
