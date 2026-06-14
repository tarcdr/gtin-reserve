<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessSupplyGenerateRequest;
use App\Http\Requests\BusinessSupplySaveRequest;
use App\Http\Requests\BusinessSupplyUpdateRequest;
use App\Models\Brand;
use App\Models\ExistingMaterial;
use App\Models\MasterLogisitcSite;
use App\Models\MasterUOM;
use App\Models\Proj12DmlFgComp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use PDO;

class BusinessSupplyController extends Controller
{
  protected $mattypes;
  protected $subMattypes;
  protected $uoms;
  protected $brands;
  protected $fgMaterials;
  protected $sites;
  protected $businessSupplies;

  public function __construct()
  {
    $this->mattypes = [
      ["code" => "1", "label" => "1"],
      ["code" => "5", "label" => "5"],
      ["code" => "7", "label" => "7"],
      ["code" => "8", "label" => "8"],
      ["code" => "9", "label" => "9"],
    ];
    $this->subMattypes = [
      ["code" => "0", "label" => "0"],
      ["code" => "1", "label" => "1"],
      ["code" => "2", "label" => "2"],
      ["code" => "3", "label" => "3"],
    ];
    $this->uoms = MasterUOM::all()->map(function ($b) {
      return [
        "value" => $b->code_uom,
        "label" => $b->description_uom,
      ];
    })->toArray();
    $this->brands = Brand::all()->map(function ($b) {
      return [
        'value' => trim((string) $b->brand),
        'label' => trim((string) $b->brand_abb) . ' - ' . trim((string) $b->brand),
      ];
    })->filter(fn ($item) => $item['value'] !== '')->values()->toArray();
    $this->fgMaterials = ExistingMaterial::query()
      ->selectRaw('TRIM(material_id) as material_id, TRIM(material_desc) as material_desc')
      ->orderByRaw('TRIM(material_id)')
      ->get()
      ->map(function ($row) {
        $materialId = trim((string) ($row->material_id ?? ''));
        $materialDesc = trim((string) ($row->material_desc ?? ''));

        return [
          'value' => $materialId,
          'label' => trim($materialId . ' - ' . $materialDesc, ' -'),
        ];
      })
      ->filter(fn ($item) => $item['value'] !== '')
      ->values()
      ->toArray();
    $this->sites = MasterLogisitcSite::all()->map(function ($b) {
      return [
        'value' => trim((string) $b->no),
        'label' => trim((string) $b->site),
      ];
    })->filter(fn ($item) => $item['value'] !== '')->values()->toArray();
    $this->businessSupplies = Proj12DmlFgComp::query()
      ->selectRaw('TRIM(BOM_FG_ID) as bom_fg_id')
      ->whereRaw('BOM_FG_ID is not null')
      ->distinct()
      ->orderByRaw('TRIM(BOM_FG_ID)')
      ->get()
      ->map(function ($row) {
        $bizsupId = trim((string) ($row->bom_fg_id ?? ''));

        if ($bizsupId === '') {
          return null;
        }

        return [
          'value' => $bizsupId,
          'label' => $bizsupId,
        ];
      })
      ->filter()
      ->unique('value')
      ->values()
      ->toArray();
  }

  public function create(Request $request): Response
  {
    return $this->new($request);
  }

  public function new(Request $request): Response
  {
    $InputData = $this->businessSupplyInput($request, 'create');

    return Inertia::render('BusinessSupply/New', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'brands' => $this->brands,
      'fgMaterials' => $this->fgMaterials,
      'sites' => $this->sites,
      'businessSupplies' => $this->businessSupplies,
    ]);
  }

  public function existing(Request $request): Response
  {
    $InputData = $this->businessSupplyInput($request, 'existing');
    $selectedBizSup = $this->resolveSelectedBusinessSupply($request);
    $InputData['bizsupId'] = $selectedBizSup['bizsupId'] ?? ($request->get('bizsupId') ?? '');
    $InputData['bizsupDesc'] = $selectedBizSup['bizsupDesc'] ?? ($request->get('bizsupDesc') ?? '');
    $InputData = array_merge($InputData, $selectedBizSup['record'] ?? []);

    return Inertia::render('BusinessSupply/Existing', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'brands' => $this->brands,
      'fgMaterials' => $this->fgMaterials,
      'sites' => $this->sites,
      'businessSupplies' => $this->businessSupplies,
      'selectedBusinessSupply' => $selectedBizSup,
    ]);
  }

  public function edit(Request $request): Response
  {
    $InputData = $this->businessSupplyInput($request, 'existing');
    $selectedBizSup = $this->resolveSelectedBusinessSupply($request);
    $InputData['bizsupId'] = $selectedBizSup['bizsupId'] ?? ($request->get('bizsupId') ?? '');
    $InputData['bizsupDesc'] = $selectedBizSup['bizsupDesc'] ?? ($request->get('bizsupDesc') ?? '');
    $InputData = array_merge($InputData, $selectedBizSup['record'] ?? []);

    return Inertia::render('BusinessSupply/Edit', [
      'InputData' => $InputData,
      'mattypes' => $this->mattypes,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'brands' => $this->brands,
      'fgMaterials' => $this->fgMaterials,
      'sites' => $this->sites,
      'businessSupplies' => $this->businessSupplies,
      'selectedBusinessSupply' => $selectedBizSup,
    ]);
  }

  protected function businessSupplyInput(Request $request, string $mode = 'create'): array
  {
    return [
      'mode' => $request->get('mode', $mode),
      'bizsupId' => $request->get('bizsupId', ''),
      'bizsupDesc' => $request->get('bizsupDesc', ''),
      'underType' => $request->get('underType', 'FG'),
      'matType' => $request->get('matType', '6'),
      'subMatType' => $request->get('subMatType', '0'),
      'fgMaterialId' => $request->get('fgMaterialId', ''),
      'brand' => $request->get('brand', ''),
      'site' => $request->get('site', ''),
      'bsId' => $request->get('bsId', ''),
      'bomBsId' => $request->get('bomBsId', ''),
      'bomBsDesc' => $request->get('bomBsDesc', ''),
      'searchDesc' => $request->get('searchDesc', ''),
      'compDescEn' => $request->get('compDescEn', ''),
      'compDescTh' => $request->get('compDescTh', ''),
      'uom' => $request->get('uom', ''),
      'productCat' => $request->get('productCat', ''),
      'prodSubCat' => $request->get('prodSubCat', ''),
      'componentId' => $request->get('componentId', ''),
      'components' => $request->get('components', []),
    ];
  }

  protected function resolveSelectedBusinessSupply(Request $request): array
  {
    $bizsupId = trim((string) $request->get('bizsupId', ''));

    if ($bizsupId === '') {
      return [
        'bizsupId' => '',
        'bizsupDesc' => '',
        'record' => null,
      ];
    }

    $record = Proj12DmlFgComp::query()
      ->whereRaw('TRIM(BOM_FG_ID) = ?', [$bizsupId])
      ->first();

    $recordData = $record ? $this->normalizeBusinessSupplyRecord($record->getAttributes()) : null;

    return [
      'bizsupId' => $bizsupId,
      'bizsupDesc' => trim((string) $request->get('bizsupDesc', '')),
      'record' => $recordData,
    ];
  }

  protected function normalizeBusinessSupplyRecord(array $record): array
  {
    $row = array_change_key_case($record, CASE_LOWER);
    $pick = function (array $source, array $keys, string $default = ''): string {
      foreach ($keys as $key) {
        if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
          return trim((string) $source[$key]);
        }
      }

      return $default;
    };

    return [
      'bomBsId' => $pick($row, ['bom_fg_id', 'bom_bs_id', 'bomsup_id']),
      'bsId' => $pick($row, ['component_id', 'bs_id', 'bizsup_id']),
      'fgMaterialId' => $pick($row, ['fg_material_id', 'material_id_fg', 'material_id_fg_1', 'fg_mat_id']),
      'site' => $pick($row, ['site', 'site_code', 'bizsup_site']),
      'brand' => $pick($row, ['brand', 'bizsup_brand']),
      'matType' => $pick($row, ['mattype', 'mat_type']),
      'subMatType' => $pick($row, ['submattype', 'sub_mat_type', 'sub_type']),
      'searchDesc' => $pick($row, ['search_desc', 'search_description', 'desc_search']),
      'compDescEn' => $pick($row, ['comp_desc_en', 'full_description_en', 'description_en']),
      'compDescTh' => $pick($row, ['comp_desc_th', 'full_description_th', 'description_th']),
      'uom' => $pick($row, ['uom', 'uom_code', 'code_uom']),
      'productCat' => $pick($row, ['product_cat', 'product_category']),
      'prodSubCat' => $pick($row, ['prod_sub_cat', 'product_sub_cat']),
      'sourceLabel' => $pick($row, ['product_cat', 'product_category', 'submattype']),
      'components' => [],
    ];
  }

  public function save(BusinessSupplySaveRequest $request): RedirectResponse|JsonResponse
  {
    $validated = $request->validated();

    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_SAVE_COMP_BOMFG(:p_bom_fg_id, :p_mattype, :p_submattype, :p_product_cat, :p_prod_sub_cat, :p_component_id, :p_search_desc, :p_comp_desc_en, :p_comp_desc_th, :p_uom, :p_user_role, :p_user, :p_error); END;');
    $stmt->bindValue(':p_bom_fg_id', (string) $validated['bomBsId'], PDO::PARAM_STR);
    $stmt->bindValue(':p_mattype', (string) $validated['matType'], PDO::PARAM_STR);
    $stmt->bindValue(':p_submattype', (string) $validated['subMatType'], PDO::PARAM_STR);
    $stmt->bindValue(':p_product_cat', (string) $validated['productCat'], PDO::PARAM_STR);
    $stmt->bindValue(':p_prod_sub_cat', (string) $validated['prodSubCat'], PDO::PARAM_STR);
    $stmt->bindValue(':p_component_id', (string) $validated['componentId'], PDO::PARAM_STR);
    $stmt->bindValue(':p_search_desc', (string) $validated['searchDesc'], PDO::PARAM_STR);
    $stmt->bindValue(':p_comp_desc_en', (string) $validated['compDescEn'], PDO::PARAM_STR);
    $stmt->bindValue(':p_comp_desc_th', (string) $validated['compDescTh'], PDO::PARAM_STR);
    $stmt->bindValue(':p_uom', (string) $validated['uom'], PDO::PARAM_STR);
    $stmt->bindValue(':p_user_role', (string) ($request->user()?->role ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':p_user', (string) ($request->user()?->user_login ?? ''), PDO::PARAM_STR);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    try {
      $stmt->execute();
    } catch (\Throwable $e) {
      Log::error('business supply save failed', [
        'error' => $e->getMessage(),
      ]);

      if ($request->expectsJson()) {
        return response()->json([
          'error' => 'Unable to save Business Supply.',
        ], 500);
      }

      return Redirect::back()->withErrors([
        'save' => 'Unable to save Business Supply.',
      ]);
    }

    if (trim((string) $error) !== '') {
      $resolvedError = $this->resolveProcedureErrorMessage($error);
      if ($request->expectsJson()) {
        return response()->json([
          'error' => $resolvedError,
        ], 422);
      }

      return Redirect::back()->withErrors([
        'save' => $resolvedError,
      ]);
    }

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'Business Supply saved.',
      ]);
    }

    return Redirect::back()->with('success', 'Business Supply saved.');
  }

  public function updateExisting(BusinessSupplyUpdateRequest $request): RedirectResponse|JsonResponse
  {
    $validated = $request->validated();
    $bomBsId = trim((string) $validated['bomBsId']);

    try {
      $updated = DB::connection('oracle')
        ->table('proj1_2_dml_fg_comp')
        ->whereRaw('TRIM(BOM_FG_ID) = ?', [$bomBsId])
        ->update([
          'SEARCH_DESC' => $validated['searchDesc'],
          'COMP_DESC_EN' => $validated['compDescEn'],
          'COMP_DESC_TH' => $validated['compDescTh'],
          'UOM' => $validated['uom'],
        ]);
    } catch (\Throwable $e) {
      Log::error('business supply update failed', [
        'error' => $e->getMessage(),
      ]);

      if ($request->expectsJson()) {
        return response()->json([
          'error' => 'Unable to update Business Supply.',
        ], 500);
      }

      return Redirect::back()->withErrors([
        'save' => 'Unable to update Business Supply.',
      ]);
    }

    if ($updated === 0) {
      if ($request->expectsJson()) {
        return response()->json([
          'error' => 'Business Supply record not found.',
        ], 404);
      }

      return Redirect::back()->withErrors([
        'save' => 'Business Supply record not found.',
      ]);
    }

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'Business Supply updated.',
      ]);
    }

    return Redirect::back()->with('success', 'Business Supply updated.');
  }

  public function generate(BusinessSupplyGenerateRequest $request): JsonResponse
  {
    $validated = $request->validated();
    $selectedValue = (string) ($validated['underType'] === 'FG'
      ? ($validated['fgMaterialId'] ?? '')
      : ($validated['underType'] === 'BRAND'
        ? ($validated['brand'] ?? '')
        : ''));

    $bsId = null;
    $bomBsId = null;
    $error = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_BS_ID(:p_mattype, :p_sub_mattype, :p_brand, :p_site, :p_bs_id, :p_bom_bs_id, :p_error); END;');
    $stmt->bindValue(':p_mattype', (string) $validated['matType'], PDO::PARAM_STR);
    $stmt->bindValue(':p_sub_mattype', (string) $validated['subMatType'], PDO::PARAM_STR);
    $stmt->bindValue(':p_brand', $selectedValue, PDO::PARAM_STR);
    $stmt->bindValue(':p_site', (string) $validated['site'], PDO::PARAM_STR);
    $stmt->bindParam(':p_bs_id', $bsId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_bom_bs_id', $bomBsId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':p_error', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    try {
      $stmt->execute();
    } catch (\Throwable $e) {
      Log::error('business supply generate failed', [
        'error' => $e->getMessage(),
      ]);

      return response()->json([
        'error' => 'Unable to generate Business Supply ID.',
      ], 500);
    }

    return response()->json([
      'program' => 'PROJ1_2_GEN_BS_ID',
      'bsId' => $bsId,
      'bomBsId' => $bomBsId,
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }

  public function createComponent(Request $request): RedirectResponse
  {
    $payload = $request->all();
    $payload['ownerLevel'] = 'businessSupply';
    $payload['subMattype'] = '0';

    return Redirect::route('packmaterial.new', $payload);
  }
}
