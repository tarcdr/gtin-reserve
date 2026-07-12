<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessSupplyGenerateRequest;
use App\Http\Requests\BusinessSupplyComponentSaveRequest;
use App\Http\Requests\BusinessSupplySaveRequest;
use App\Http\Requests\BusinessSupplyUpdateRequest;
use App\Models\ExistingMaterial;
use App\Models\MasterLogisitcSite;
use App\Models\MasterUOM;
use App\Models\Proj12BizSupId;
use App\Models\Proj12BrandV;
use App\Models\Proj12DmlBizsupBom;
use App\Models\Proj12DmlBizsupCompMatId;
use App\Models\Proj12DmlBizsupCompM6;
use Illuminate\Database\Eloquent\Model;
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
  protected $subMattypes;
  protected $uoms;
  protected $brands;
  protected $fgMaterials;
  protected $sites;
  protected $businessSupplies;

  public function __construct()
  {
    $this->subMattypes = [
      ['code' => '0', 'label' => '0'],
      ['code' => '1', 'label' => '1'],
      ['code' => '2', 'label' => '2'],
      ['code' => '3', 'label' => '3'],
    ];
    $this->uoms = MasterUOM::all()->map(function ($row) {
      return [
        'value' => trim((string) $row->code_uom),
        'label' => trim((string) $row->description_uom),
      ];
    })->toArray();
    $this->brands = Proj12BrandV::query()
      ->selectRaw('TRIM(BRAND_ABB) as brand_abb, TRIM(BRAND_LIST) as brand_list')
      ->orderByRaw('TRIM(BRAND_LIST)')
      ->get()
      ->map(function ($row) {
        $brandAbb = trim((string) ($row->brand_abb ?? ''));
        $brandList = trim((string) ($row->brand_list ?? ''));

        return [
          'value' => $brandAbb,
          'label' => $brandList,
        ];
      })
      ->filter(fn ($item) => $item['value'] !== '' && $item['label'] !== '')
      ->values()
      ->toArray();
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
    $this->sites = MasterLogisitcSite::all()->map(function ($row) {
      return [
        'value' => trim((string) $row->no),
        'label' => trim((string) $row->site),
      ];
    })->filter(fn ($item) => $item['value'] !== '')->values()->toArray();
    $this->businessSupplies = Proj12BizSupId::query()
      ->selectRaw('TRIM(BIZSUP_BOM_ID) as bizsup_bom_id, TRIM(DESC_BIZSUP_ID) as desc_bizsup_id')
      ->whereRaw('TRIM(BIZSUP_BOM_ID) IS NOT NULL')
      ->orderByRaw('TRIM(BIZSUP_BOM_ID)')
      ->get()
      ->map(fn ($row) => $this->normalizeBusinessSupplyOption($row))
      ->filter(fn ($item) => $item !== null)
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
    $InputData['bizsupId'] = $selectedBizSup['bizsupId'] ?? $this->requestString($request, 'bizsupId');
    $InputData['bizsupDesc'] = $selectedBizSup['bizsupDesc'] ?? $this->requestString($request, 'bizsupDesc');
    $InputData = array_merge($InputData, $selectedBizSup['record'] ?? []);

    return Inertia::render('BusinessSupply/Existing', [
      'InputData' => $InputData,
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
    $InputData['bizsupId'] = $selectedBizSup['bizsupId'] ?? $this->requestString($request, 'bizsupId');
    $InputData['bizsupDesc'] = $selectedBizSup['bizsupDesc'] ?? $this->requestString($request, 'bizsupDesc');
    $InputData = array_merge($InputData, $selectedBizSup['record'] ?? []);

    return Inertia::render('BusinessSupply/Edit', [
      'InputData' => $InputData,
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
      'mode' => $this->requestString($request, 'mode') !== '' ? $this->requestString($request, 'mode') : $mode,
      'bizsupId' => $this->requestString($request, 'bizsupId'),
      'bizsupDesc' => $this->requestString($request, 'bizsupDesc'),
      'underType' => $this->requestString($request, 'underType'),
      'matType' => $this->requestString($request, 'matType'),
      'subMatType' => $this->requestString($request, 'subMatType'),
      'fgMaterialId' => $this->requestString($request, 'fgMaterialId'),
      'brand' => $this->requestString($request, 'brand'),
      'site' => $this->requestString($request, 'site'),
      'bsId' => $this->requestString($request, 'bsId'),
      'bomBsId' => $this->requestString($request, 'bomBsId'),
      'bomBsDesc' => $this->requestString($request, 'bomBsDesc'),
      'searchDesc' => $this->requestString($request, 'searchDesc'),
      'compDescEn' => $this->requestString($request, 'compDescEn'),
      'compDescTh' => $this->requestString($request, 'compDescTh'),
      'uom' => $this->requestString($request, 'uom'),
      'productCat' => $this->requestString($request, 'productCat'),
      'prodSubCat' => $this->requestString($request, 'prodSubCat'),
      'componentId' => $this->requestString($request, 'componentId'),
      'components' => $request->input('components', []),
    ];
  }

  protected function requestString(Request $request, string $key): string
  {
    return trim((string) $request->input($key, ''));
  }

  protected function normalizeBusinessSupplyOption($row): ?array
  {
    $record = $this->toLowercaseArray($row);
    $bomBsId = $this->pick($record, ['bizsup_bom_id', 'bom_bs_id', 'bomsup_id']);
    $bizsupDesc = $this->pick($record, ['desc_bizsup_id', 'bom_bs_desc', 'desc_bom_bs_id']);

    if ($bomBsId === '' && $bizsupDesc === '') {
      return null;
    }

    return [
      'value' => $bomBsId,
      'label' => $bizsupDesc !== '' ? trim($bomBsId . ' - ' . $bizsupDesc, ' -') : $bomBsId,
    ];
  }

  protected function resolveSelectedBusinessSupply(Request $request): array
  {
    $bizsupId = $this->requestString($request, 'bizsupId');
    $bsId = $this->requestString($request, 'bsId');
    $selectedKey = $bizsupId !== '' ? $bizsupId : $bsId;

    if ($selectedKey === '') {
      return [
        'bizsupId' => '',
        'bizsupDesc' => '',
        'record' => null,
      ];
    }

    $recordRow = Proj12BizSupId::query()
      ->whereRaw('TRIM(BIZSUP_BOM_ID) = ?', [$selectedKey])
      ->orderByRaw('NVL(NO, 0) DESC')
      ->first();
    $record = $recordRow ? $this->normalizeBusinessSupplyIdRecord($recordRow) : [];

    return [
      'bizsupId' => $record['bomBsId'] ?? $selectedKey,
      'bizsupDesc' => $record['sourceLabel'] ?? $this->requestString($request, 'bizsupDesc'),
      'record' => $record !== [] ? $record : null,
    ];
  }

  protected function normalizeBusinessSupplyIdRecord($row): array
  {
    $record = $this->toLowercaseArray($row);
    $underType = $this->normalizeUnderType(
      $this->pick($record, ['type_bs', 'under_type', 'product_cat'])
    );
    $bomBsId = $this->pick($record, ['bizsup_bom_id', 'bom_bs_id']);

    $normalized = [
      'bomBsId' => $bomBsId,
      'bomBsDesc' => $this->resolveBusinessSupplyBomDescription($bomBsId),
      'bsId' => $this->pick($record, ['bizsup_id', 'bs_id']),
      'fgMaterialId' => $this->pick($record, ['material_id_fg_1', 'material_id_fg', 'fg_material_id']),
      'brand' => $this->pick($record, ['brand_abb', 'brand']),
      'site' => $this->pick($record, ['site']),
      'matType' => $this->pick($record, ['mattype', 'mat_type'], '6'),
      'subMatType' => $this->pick($record, ['sub_mattype', 'submattype'], '0'),
      'productCat' => $underType,
      'searchDesc' => $this->pick($record, ['desc_bizsup_id', 'search_description', 'search_desc']),
      'compDescEn' => $this->pick($record, ['full_desc_bizsup_id_en', 'full_description_en', 'comp_desc_en']),
      'compDescTh' => $this->pick($record, ['full_desc_bizsup_id_th', 'full_description_th', 'comp_desc_th']),
      'uom' => $this->pick($record, ['uom']),
      'prodSubCat' => '',
      'componentId' => $this->pick($record, ['bizsup_id', 'bs_id']),
      'components' => [],
      'sourceLabel' => trim(
        $this->pick($record, ['bizsup_bom_id', 'bom_bs_id']) . ' - ' . $this->pick($record, ['desc_bizsup_id', 'bom_bs_desc', 'desc_bom_bs_id']),
        ' -'
      ),
    ];

    $normalized['components'] = $this->loadBusinessSupplyComponents($normalized);

    return $normalized;
  }

  protected function loadBusinessSupplyComponents(array $record): array
  {
    $matIdComponents = $this->loadBusinessSupplyComponentsFromModel(
      Proj12DmlBizsupCompMatId::class,
      $record,
      'matid'
    );
    $m6Components = $this->loadBusinessSupplyComponentsFromModel(
      Proj12DmlBizsupCompM6::class,
      $record,
      'component'
    );

    return collect([...$matIdComponents, ...$m6Components])
      ->values()
      ->all();
  }

  protected function loadBusinessSupplyComponentsFromModel(string $modelClass, array $record, string $sourceType): array
  {
    $sampleRow = $modelClass::query()->first();

    if (!$sampleRow) {
      return [];
    }

    $availableColumns = array_change_key_case($sampleRow->getAttributes(), CASE_UPPER);
    $linkPreferences = $sourceType === 'component'
      ? ['BIZSUP_ID', 'BIZSUP_BOM_ID']
      : ['BIZSUP_BOM_ID', 'BIZSUP_ID'];
    $linkColumn = collect($linkPreferences)->first(fn ($column) => array_key_exists($column, $availableColumns));

    if (!$linkColumn) {
      return [];
    }

    $candidateValues = array_values(array_unique(array_filter([
      trim((string) ($record['bsId'] ?? '')),
      trim((string) ($record['bomBsId'] ?? '')),
    ], fn ($value) => $value !== '')));

    if ($candidateValues === []) {
      return [];
    }

    $rows = collect();
    foreach ($candidateValues as $candidateValue) {
      $queryRows = $modelClass::query()
        ->whereRaw("TRIM({$linkColumn}) = ?", [$candidateValue])
        ->get();

      if ($queryRows->isNotEmpty()) {
        $rows = $queryRows;
        break;
      }
    }

    if ($rows->isEmpty()) {
      return [];
    }

    return $rows
      ->map(function ($row, $index) use ($sourceType) {
        $source = $this->toLowercaseArray($row);
        $code = $this->pick($source, [
          'material_id_bizsup_comp_mat_id',
          'material_id_bizsup_comp_m6',
          'component_id',
          'material_id',
          'bizsup_id',
        ]);
        $description = $this->pick($source, [
          'search_description',
          'full_description_en',
          'desc_bizsup_id',
          'description',
        ]);

        return [
          'id' => $this->pick($source, ['no'], (string) $index),
          'code' => $code,
          'label' => $description,
          'description' => $description,
          'sourceType' => $sourceType,
        ];
      })
      ->filter(fn ($item) => trim((string) $item['code']) !== '')
      ->values()
      ->all();
  }

  protected function toLowercaseArray($row): array
  {
    $attributes = $row instanceof Model ? $row->getAttributes() : (array) $row;

    return array_change_key_case($attributes, CASE_LOWER);
  }

  protected function pick(array $source, array $keys, string $default = ''): string
  {
    foreach ($keys as $key) {
      if (array_key_exists($key, $source) && trim((string) $source[$key]) !== '') {
        return trim((string) $source[$key]);
      }
    }

    return $default;
  }

  protected function mergeNonEmptyValues(array $base, array $overlay): array
  {
    foreach ($overlay as $key => $value) {
      if (is_array($value)) {
        if ($value !== []) {
          $base[$key] = $value;
        }
        continue;
      }

      if (trim((string) $value) !== '') {
        $base[$key] = $value;
      }
    }

    return $base;
  }

  protected function businessSupplyComponentExists(string $bomBsId, string $componentId): bool
  {
    $bomBsId = trim($bomBsId);
    $componentId = trim($componentId);

    if ($bomBsId === '' || $componentId === '') {
      return false;
    }

    return Proj12DmlBizsupCompM6::query()
      ->whereRaw('TRIM(BIZSUP_ID) = ?', [$bomBsId])
      ->whereRaw('TRIM(MATERIAL_ID_BIZSUP_COMP_M6) = ?', [$componentId])
      ->exists();
  }

  protected function fallbackInsertBusinessSupplyComponent(array $data, string $userLogin, string $userRole): void
  {
    $maxNo = Proj12DmlBizsupCompM6::query()->max('NO');
    $nextNo = is_numeric($maxNo) ? ((int) $maxNo) + 1 : 1;

    DB::connection('oracle')->table('PROJ1_2_DML_BIZSUP_COMP_M6')->insert([
      'NO' => $nextNo,
      'MATERIAL_ID_BIZSUP_COMP_M6' => $data['componentId'],
      'SEARCH_DESCRIPTION' => $data['searchDesc'],
      'FULL_DESCRIPTION_EN' => $data['compDescEn'],
      'FULL_DESCRIPTION_TH' => $data['compDescTh'],
      'BIZSUP_ID' => $data['bomBsId'],
      'SITE' => $data['site'],
      'UOM' => $data['uom'],
      'STATUS_ROW' => 'INS',
      'USER_ROLE' => $userRole,
      'USER_CREATE' => $userLogin,
      'CREATE_DATE' => now(),
      'USER_UPDATE' => null,
      'UPDATE_DATE' => null,
    ]);
  }

  protected function normalizeUnderType(string $value): string
  {
    $normalized = strtoupper(trim($value));

    return match ($normalized) {
      '1', 'FG' => 'FG',
      '2', 'BRAND' => 'BRAND',
      '3', 'NOT ALL', 'NOT_ALL' => 'NOT ALL',
      default => $normalized,
    };
  }

  protected function resolveBrandLabel(string $brandAbb): string
  {
    $brandAbb = trim($brandAbb);

    if ($brandAbb === '') {
      return '';
    }

    foreach ($this->brands as $brand) {
      if (trim((string) ($brand['value'] ?? '')) === $brandAbb) {
        return trim((string) ($brand['label'] ?? ''));
      }
    }

    return '';
  }

  protected function resolveBusinessSupplyBomDescription(string $bomBsId): string
  {
    $bomBsId = trim($bomBsId);

    if ($bomBsId === '') {
      return '';
    }

    $row = Proj12DmlBizsupBom::query()
      ->selectRaw('TRIM(DESC_BIZSUP_BOM_ID) as desc_bizsup_bom_id')
      ->whereRaw('TRIM(BIZSUP_BOM_ID) = ?', [$bomBsId])
      ->orderByRaw('NVL(NO, 0) DESC')
      ->first();

    return trim((string) ($row->desc_bizsup_bom_id ?? ''));
  }

  protected function loadBusinessSupplyBomRecord(string $bomBsId): ?array
  {
    $bomBsId = trim($bomBsId);

    if ($bomBsId === '') {
      return null;
    }

    $row = Proj12DmlBizsupBom::query()
      ->whereRaw('TRIM(BIZSUP_BOM_ID) = ?', [$bomBsId])
      ->orderByRaw('NVL(NO, 0) DESC')
      ->first();

    if (!$row) {
      return null;
    }

    $source = $this->toLowercaseArray($row);
    $bomBsDesc = $this->pick($source, ['desc_bizsup_bom_id', 'bom_bs_desc', 'desc_bom_bs_id']);
    $searchDesc = $this->pick($source, ['search_description', 'desc_bizsup_bom_search', 'desc_bizsup_bom_id_search']);
    $compDescEn = $this->pick($source, ['full_description_en', 'desc_bizsup_bom_en', 'full_desc_en']);
    $compDescTh = $this->pick($source, ['full_description_th', 'desc_bizsup_bom_th', 'full_desc_th']);

    return [
      'bomBsId' => trim((string) ($source['bizsup_bom_id'] ?? $bomBsId)),
      'bomBsDesc' => $bomBsDesc,
      'searchDesc' => $searchDesc !== '' ? $searchDesc : $bomBsDesc,
      'compDescEn' => $compDescEn !== '' ? $compDescEn : $bomBsDesc,
      'compDescTh' => $compDescTh !== '' ? $compDescTh : $bomBsDesc,
      'uom' => $this->pick($source, ['uom']),
      'site' => $this->pick($source, ['site']),
      'matType' => $this->pick($source, ['mattype', 'mat_type'], '6'),
      'subMatType' => $this->pick($source, ['sub_mattype', 'submattype'], '0'),
      'productCat' => $this->pick($source, ['product_cat', 'productcat']),
      'prodSubCat' => $this->pick($source, ['prod_sub_cat', 'prodsubcat']),
    ];
  }

  protected function saveBusinessSupplyId(array $validated, Request $request): RedirectResponse|JsonResponse|null
  {
    $underType = trim((string) $validated['underType']);
    $bomBsId = trim((string) $validated['bomBsId']);
    $bomBsDesc = trim((string) ($validated['bomBsDesc'] ?? ''));
    $bsId = trim((string) $validated['bsId']);
    $matType = trim((string) $validated['matType']);
    $subMatType = trim((string) $validated['subMatType']);
    $fgMaterialId = $underType === 'FG' ? trim((string) ($validated['fgMaterialId'] ?? '')) : '';
    $brandAbb = $underType === 'BRAND' ? trim((string) ($validated['brand'] ?? '')) : '';
    $brand = $underType === 'BRAND' ? $this->resolveBrandLabel($brandAbb) : '';
    $site = trim((string) $validated['site']);
    $searchDesc = trim((string) $validated['searchDesc']);
    $compDescEn = trim((string) $validated['compDescEn']);
    $compDescTh = trim((string) $validated['compDescTh']);
    $uom = trim((string) $validated['uom']);
    $userRole = (string) ($request->user()?->role ?? '');
    $userLogin = (string) ($request->user()?->user_login ?? '');
    $error = '';

    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_SAVE_BS_ID(:P_TYPE_BS, :P_MATTYPE, :P_SUB_MATTYPE, :P_MATERIAL_ID_FG_1, :P_BRAND, :P_BRAND_ABB, :P_BS_BOM_ID, :P_BS_BOM_ID_DESC, :P_BS_ID, :P_BS_ID_SEARCH_DESC, :P_BS_ID_DESC_EN, :P_BS_ID_DESC_TH, :P_UOM, :P_SITE, :P_USER_LOGIN, :P_USER_ROLE, :P_ERROR); END;');
    $stmt->bindValue(':P_TYPE_BS', $underType, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $matType, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUB_MATTYPE', $subMatType, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATERIAL_ID_FG_1', $fgMaterialId, PDO::PARAM_STR);
    $stmt->bindValue(':P_BRAND', $brand, PDO::PARAM_STR);
    $stmt->bindValue(':P_BRAND_ABB', $brandAbb, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_BOM_ID', $bomBsId, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_BOM_ID_DESC', $bomBsDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_ID', $bsId, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_ID_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_ID_DESC_EN', $compDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_BS_ID_DESC_TH', $compDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_SITE', $site, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_LOGIN', $userLogin, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', $userRole, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    try {
      $stmt->execute();
    } catch (\Throwable $e) {
      Log::error('business supply save failed', [
        'error' => $e->getMessage(),
        'bomBsId' => $bomBsId,
        'bsId' => $bsId,
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
        'program' => 'PROJ1_2_SAVE_BS_ID',
        'bsId' => $bsId,
        'bomBsId' => $bomBsId,
      ]);
    }

    return null;
  }

  public function save(BusinessSupplySaveRequest $request): RedirectResponse|JsonResponse
  {
    $validated = $request->validated();
    $result = $this->saveBusinessSupplyId($validated, $request);

    if ($result instanceof RedirectResponse || $result instanceof JsonResponse) {
      return $result;
    }

    return Redirect::back()->with('success', 'Business Supply saved.');
  }

  public function updateExisting(BusinessSupplyUpdateRequest $request): RedirectResponse|JsonResponse
  {
    $validated = $request->validated();
    $result = $this->saveBusinessSupplyId($validated, $request);

    if ($result instanceof RedirectResponse || $result instanceof JsonResponse) {
      return $result;
    }

    return Redirect::back()->with('success', 'Business Supply updated.');
  }

  public function generate(BusinessSupplyGenerateRequest $request): JsonResponse
  {
    $validated = $request->validated();
    $underType = (string) $validated['underType'];
    $typeBs = match ($underType) {
      'FG' => '1',
      'BRAND' => '2',
      'NOT ALL' => '3',
      default => $underType,
    };
    $matType = (string) $validated['matType'];
    $subMatType = (string) $validated['subMatType'];
    $materialIdFg = $underType === 'FG' ? (string) ($validated['fgMaterialId'] ?? '') : '';
    $brandAbb = $underType === 'BRAND' ? (string) ($validated['brand'] ?? '') : '';
    $site = (string) $validated['site'];
    $userLogin = (string) ($request->user()?->user_login ?? '');

    $bsId = '';
    $bomBsId = '';
    $error = '';
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_BS_ID(:p_type_bs, :p_mattype, :p_sub_mattype, :p_material_id_fg, :p_brand, :p_site, :p_user_login, :p_bs_id, :p_bom_bs_id, :p_error); END;');
    $stmt->bindParam(':p_type_bs', $typeBs, PDO::PARAM_STR);
    $stmt->bindParam(':p_mattype', $matType, PDO::PARAM_STR);
    $stmt->bindParam(':p_sub_mattype', $subMatType, PDO::PARAM_STR);
    $stmt->bindParam(':p_material_id_fg', $materialIdFg, PDO::PARAM_STR);
    $stmt->bindParam(':p_brand', $brandAbb, PDO::PARAM_STR);
    $stmt->bindParam(':p_site', $site, PDO::PARAM_STR);
    $stmt->bindParam(':p_user_login', $userLogin, PDO::PARAM_STR);
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

    $resolvedError = $this->resolveProcedureErrorMessage($error);

    return response()->json([
      'program' => 'PROJ1_2_GEN_BS_ID',
      'bsId' => trim((string) $bsId),
      'bomBsId' => trim((string) $bomBsId),
      'error' => $resolvedError,
    ]);
  }

  protected function buildBusinessSupplyComponentInput(Request $request, array $selectedBizSup, array $record): array
  {
    $bizsupId = $selectedBizSup['bizsupId'] ?? $this->requestString($request, 'bizsupId');
    $actionMode = $this->requestString($request, 'actionMode') !== '' ? $this->requestString($request, 'actionMode') : 'create';
    $componentId = $this->requestString($request, 'componentId');
    $isEditMode = in_array($actionMode, ['edit', 'delete'], true);
    $bomRecord = $this->loadBusinessSupplyBomRecord($bizsupId) ?? [];
    $resolvedRecord = $isEditMode
      ? $this->mergeNonEmptyValues($bomRecord, $record)
      : $this->mergeNonEmptyValues($record, $bomRecord);
    $searchDesc = $isEditMode ? ($resolvedRecord['searchDesc'] ?? '') : '';
    $fullDescEn = $isEditMode ? ($resolvedRecord['compDescEn'] ?? '') : '';
    $fullDescTh = $isEditMode ? ($resolvedRecord['compDescTh'] ?? '') : '';
    $uom = $isEditMode ? ($resolvedRecord['uom'] ?? '') : '';

    return [
      'mode' => $actionMode,
      'actionMode' => $actionMode,
      'ownerLevel' => 'businessSupply',
      'backRoute' => 'business-supply.existing',
      'bizsupId' => $bizsupId,
      'bizsupDesc' => $selectedBizSup['bizsupDesc'] ?? '',
      'bomId' => $resolvedRecord['bomBsId'] ?? $bizsupId,
      'bomBsId' => $resolvedRecord['bomBsId'] ?? $bizsupId,
      'bomDesc' => $resolvedRecord['bomBsDesc'] ?? '',
      'bomBsDesc' => $resolvedRecord['bomBsDesc'] ?? '',
      'mattype' => '6',
      'matType' => '6',
      'subMattype' => $resolvedRecord['subMatType'] ?? '0',
      'subMatType' => $resolvedRecord['subMatType'] ?? '0',
      'site' => $resolvedRecord['site'] ?? '',
      'searchDesc' => $searchDesc,
      'fullDescEn' => $fullDescEn,
      'fullDescTh' => $fullDescTh,
      'uom' => $uom,
      'productCat' => $resolvedRecord['productCat'] ?? '',
      'productSubCat' => $resolvedRecord['prodSubCat'] ?? '',
      'componentId' => $isEditMode
        ? ($componentId !== '' ? $componentId : ($record['componentId'] ?? ''))
        : '',
      'ownerDetail' => [
        'id' => $bizsupId,
        'searchDesc' => $searchDesc,
        'fullDescEn' => $fullDescEn,
        'fullDescTh' => $fullDescTh,
        'uom' => $uom,
        'site' => $resolvedRecord['site'] ?? '',
      ],
    ];
  }

  public function createComponent(Request $request): Response
  {
    $selectedBizSup = $this->resolveSelectedBusinessSupply($request);
    $record = $selectedBizSup['record'] ?? [];
    $request->merge(['actionMode' => 'create']);
    $InputData = $this->buildBusinessSupplyComponentInput($request, $selectedBizSup, $record);

    return Inertia::render('BusinessSupply/Component', [
      'InputData' => $InputData,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'selectedBusinessSupply' => $selectedBizSup,
    ]);
  }

  protected function loadBusinessSupplyComponentForEdit(string $bizsupId, string $componentId): ?array
  {
    $bizsupId = trim($bizsupId);
    $componentId = trim($componentId);

    if ($bizsupId === '' || $componentId === '') {
      return null;
    }

    $row = Proj12DmlBizsupCompM6::query()
      ->whereRaw('TRIM(BIZSUP_ID) = ?', [$bizsupId])
      ->whereRaw('TRIM(MATERIAL_ID_BIZSUP_COMP_M6) = ?', [$componentId])
      ->first();

    if (!$row) {
      return null;
    }

    $source = $this->toLowercaseArray($row);
    $componentIdValue = trim((string) ($source['material_id_bizsup_comp_m6'] ?? $componentId));
    $productSubCat = $this->pick($source, ['product_sub_cat', 'prod_sub_cat']);
    $productCat = $this->pick($source, ['product_cat', 'product_category']);

    if ($productSubCat === '' && $componentIdValue !== '') {
      $productSubCat = substr($componentIdValue, 0, 4);
    }

    if ($productCat === '' && $componentIdValue !== '') {
      $productCat = substr($componentIdValue, 0, 2);
    }

    return [
      'bomBsId' => trim((string) ($source['bizsup_id'] ?? $bizsupId)),
      'bomBsDesc' => $this->resolveBusinessSupplyBomDescription($bizsupId),
      'bsId' => trim((string) ($source['bizsup_id'] ?? $bizsupId)),
      'fgMaterialId' => '',
      'brand' => '',
      'site' => trim((string) ($source['site'] ?? '')),
      'matType' => trim((string) ($source['mattype'] ?? '6')),
      'mattype' => trim((string) ($source['mattype'] ?? '6')),
      'subMatType' => trim((string) ($source['sub_mattype'] ?? '0')),
      'subMattype' => trim((string) ($source['sub_mattype'] ?? '0')),
      'productCat' => $productCat,
      'prodSubCat' => $productSubCat,
      'componentId' => $componentIdValue,
      'searchDesc' => trim((string) ($source['search_description'] ?? '')),
      'compDescEn' => trim((string) ($source['full_description_en'] ?? '')),
      'compDescTh' => trim((string) ($source['full_description_th'] ?? '')),
      'uom' => trim((string) ($source['uom'] ?? '')),
      'sourceLabel' => trim((string) ($source['bizsup_id'] ?? $bizsupId)),
    ];
  }

  public function editComponent(Request $request): Response
  {
    $bizsupId = $this->requestString($request, 'bizsupId');
    $componentId = $this->requestString($request, 'componentId');
    $actionMode = $this->requestString($request, 'actionMode') !== '' ? $this->requestString($request, 'actionMode') : 'edit';
    $selectedBizSup = $this->resolveSelectedBusinessSupply($request);
    $record = $selectedBizSup['record'] ?? [];
    $editRecord = $this->loadBusinessSupplyComponentForEdit($bizsupId ?: ($selectedBizSup['bizsupId'] ?? ''), $componentId) ?? [];

    $request->merge(['actionMode' => $actionMode]);
    $InputData = $this->buildBusinessSupplyComponentInput(
      $request,
      $selectedBizSup,
      $this->mergeNonEmptyValues($record, $editRecord)
    );

    return Inertia::render('BusinessSupply/Component', [
      'InputData' => $InputData,
      'subMattypes' => $this->subMattypes,
      'uoms' => $this->uoms,
      'selectedBusinessSupply' => $selectedBizSup,
    ]);
  }

  public function saveComponent(BusinessSupplyComponentSaveRequest $request): RedirectResponse|JsonResponse
  {
    $validated = $request->validated();
    $actionMode = $this->requestString($request, 'actionMode') !== '' ? $this->requestString($request, 'actionMode') : 'create';
    $bomBsId = trim((string) ($validated['bomId'] ?? $validated['bomBsId'] ?? $request->input('bomBsId', '') ?? $request->input('bizsupId', '')));
    $componentId = trim((string) $validated['componentId']);
    $matType = trim((string) ($validated['matType'] ?? $validated['mattype'] ?? $request->input('mattype', '')));
    $subMatType = trim((string) ($validated['subMatType'] ?? $validated['subMattype'] ?? $request->input('subMattype', '')));
    $productCat = trim((string) $validated['productCat']);
    $productSubCat = trim((string) $validated['productSubCat']);
    $site = trim((string) $validated['site']);
    $searchDesc = trim((string) $validated['searchDesc']);
    $compDescEn = trim((string) $validated['fullDescEn']);
    $compDescTh = trim((string) $validated['fullDescTh']);
    $uom = trim((string) $validated['uom']);
    $userRole = (string) ($request->user()?->role ?? '');
    $userLogin = (string) ($request->user()?->user_login ?? '');
    $error = '';

    Log::info('business supply component save requested', [
      'actionMode' => $actionMode,
      'bomBsId' => $bomBsId,
      'componentId' => $componentId,
      'matType' => $matType,
      'subMatType' => $subMatType,
      'productCat' => $productCat,
      'productSubCat' => $productSubCat,
      'site' => $site,
      'searchDesc' => $searchDesc,
      'fullDescEn' => $compDescEn,
      'fullDescTh' => $compDescTh,
      'uom' => $uom,
      'userRole' => $userRole,
      'userLogin' => $userLogin,
    ]);

    $pdo = DB::connection('oracle')->getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_SAVE_COMP_BOM_BS_M6(:P_BOM_BS_ID, :P_MATTYPE, :P_SUBMATTYPE, :P_PRODUCT_CAT, :P_PROD_SUB_CAT, :P_SITE, :P_COMPONENT_ID, :P_SEARCH_DESC, :P_COMP_DESC_EN, :P_COMP_DESC_TH, :P_UOM, :P_USER_ROLE, :P_USER, :P_ERROR); END;');
    $stmt->bindValue(':P_BOM_BS_ID', $bomBsId, PDO::PARAM_STR);
    $stmt->bindValue(':P_MATTYPE', $matType, PDO::PARAM_STR);
    $stmt->bindValue(':P_SUBMATTYPE', $subMatType, PDO::PARAM_STR);
    $stmt->bindValue(':P_PRODUCT_CAT', $productCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_PROD_SUB_CAT', $productSubCat, PDO::PARAM_STR);
    $stmt->bindValue(':P_SITE', $site, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMPONENT_ID', $componentId, PDO::PARAM_STR);
    $stmt->bindValue(':P_SEARCH_DESC', $searchDesc, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_EN', $compDescEn, PDO::PARAM_STR);
    $stmt->bindValue(':P_COMP_DESC_TH', $compDescTh, PDO::PARAM_STR);
    $stmt->bindValue(':P_UOM', $uom, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER_ROLE', $userRole, PDO::PARAM_STR);
    $stmt->bindValue(':P_USER', $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    try {
      $stmt->execute();
    } catch (\Throwable $e) {
      Log::error('business supply component save failed', [
        'error' => $e->getMessage(),
        'bomBsId' => $bomBsId,
        'componentId' => $componentId,
      ]);

      if ($request->expectsJson()) {
        return response()->json([
          'error' => 'Unable to save Business Supply Component.',
        ], 500);
      }

      return Redirect::back()->withErrors([
        'save' => 'Unable to save Business Supply Component.',
      ]);
    }

    Log::info('business supply component save executed', [
      'bomBsId' => $bomBsId,
      'componentId' => $componentId,
      'procError' => $error,
    ]);

    $resolvedError = $this->resolveProcedureErrorMessage($error);
    if ($resolvedError !== '') {
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
        'message' => 'Business Supply Component saved.',
        'program' => 'PROJ1_2_SAVE_COMP_BOM_BS_M6',
        'bizsupId' => $bomBsId,
        'componentId' => $componentId,
      ]);
    }

    return Redirect::route('business-supply.existing', [
      'bizsupId' => $bomBsId !== '' ? $bomBsId : $request->input('bizsupId', ''),
    ])->setStatusCode(303);
  }

  public function generateComponentId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'productSubCat' => ['required'],
    ]);

    $componentId = null;
    $error = null;
    $userLogin = (string) ($request->user()?->user_login ?? 'system');
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_COMP_BOM_BS_M6(:p_bs_sub_cat, :p_user_login, :p_componenid, :P_ERROR); END;');
    $stmt->bindParam(':p_bs_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
    $stmt->bindParam(':p_user_login', $userLogin, PDO::PARAM_STR);
    $stmt->bindParam(':p_componenid', $componentId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->bindParam(':P_ERROR', $error, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    try {
      $stmt->execute();
    } catch (\Throwable $e) {
      Log::error('business supply generate component id failed', [
        'error' => $e->getMessage(),
      ]);

      return response()->json([
        'error' => 'Unable to generate Business Supply Component ID.',
      ], 500);
    }

    return response()->json([
      'program' => 'PROJ1_2_GEN_COMP_BOM_BS_M6',
      'componentId' => trim((string) $componentId),
      'error' => $this->resolveProcedureErrorMessage($error),
    ]);
  }
}
