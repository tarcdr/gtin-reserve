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
    $InputData = [
      "mattype"    => "5",
      "subMattype" => $request->subMattype ?? '',
      'actionMode' => $request->actionMode ?: 'create',
      'ownerLevel' => $request->ownerLevel ?: 'fg',
      'backRoute' => $request->backRoute ?: 'product.view',
      'backMaterialId' => $request->backMaterialId ?: $request->materialId ?: $request->fgMaterialId,
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
      'fgDetail'   => $request->get('fgDetail', []),
      'ownerDetail' => $request->get('ownerDetail', []),
      'components' => $request->get('components', []),
      'fgMaterialId' => $request->fgMaterialId,
      'fgBomId'    => $request->fgBomId,
      'fgBomDesc'  => $request->fgBomDesc,
      'levelMaterialId' => $request->levelMaterialId,
      'levelSearchDesc' => $request->levelSearchDesc,
      'levelFullDescEn' => $request->levelFullDescEn,
      'levelFullDescTh' => $request->levelFullDescTh,
      'levelUom'   => $request->levelUom,
      'productCat' => $request->productCat,
      'productSubCat' => $request->productSubCat,
      'componentId' => $request->componentId,
      'searchDesc' => $request->searchDesc,
      'fullDescEn' => $request->fullDescEn,
      'fullDescTh' => $request->fullDescTh,
      'uom'       => $request->uom,
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
    $componentId = $request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    );

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
