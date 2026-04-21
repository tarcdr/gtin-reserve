<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PackMaterialCreateRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;
use App\Models\MasterUOM;
use App\Services\MasterCatLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use PDO;

class PackMaterialController extends Controller
{
  protected $brands;
  protected $materials;
  protected $uoms;
  protected $productCategories;
  protected $productSubCategories;

  public function __construct(MasterCatLookup $mk)
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
    $allowedCategories = ['50', '51', '52'];
    $this->productCategories = $mk->categories()
      ->filter(fn($item) => in_array($item->code, $allowedCategories, true))
      ->map(fn($item) => [
        'code' => $item->code,
        'label' => $item->name,
      ])
      ->values()
      ->toArray();
    $this->productSubCategories = collect($allowedCategories)
      ->flatMap(function ($code) use ($mk) {
        return $mk->subcategoriesOf($code)->map(fn($item) => [
          'productCatCode' => $code,
          'code' => $item->code,
          'label' => $item->name,
        ]);
      })
      ->values()
      ->toArray();
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

  static $subMattypes = [[
    "code" => "0",
    "label" => "0"
  ], [
    "code" => "1",
    "label" => "1"
  ], [
    "code" => "2",
    "label" => "2"
  ], [
    "code" => "3",
    "label" => "3"
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
      return Redirect::route('material-levels.business-supply.new', [
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
    $subMattypes = self::$subMattypes;
    $InputData = [
      "mattype"    => "5",
      "subMattype" => $request->subMattype ?: "0",
      'actionMode' => $request->actionMode ?: 'create',
      'ownerLevel' => $request->ownerLevel ?: 'fg',
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
    $productCategories = $this->productCategories;
    $productSubCategories = $this->productSubCategories;
    return Inertia::render('PackMaterial/New', compact('InputData', 'mattypes', 'subMattypes', 'uoms', 'productCategories', 'productSubCategories'));
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
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_COMP_BOMFG(:p_prd_sub_cat, :p_componenid); END;');
    $stmt->bindParam(':p_prd_sub_cat', $validated['productSubCat'], PDO::PARAM_STR);
    $stmt->bindParam(':p_componenid', $componentId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_COMP_BOMFG',
      'componentId' => $componentId,
    ]);
  }
}
