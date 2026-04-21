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
use App\Models\MasterUOM;
use App\Models\MasterLogisitcSite;
use App\Services\MasterCatLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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

  protected function buildProductInput(Request $request): array
  {
    $normalizeComponents = function ($items) {
      return collect($items ?? [])
        ->filter(fn($item) => is_array($item) && !empty($item['code']))
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
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $masterUom = $this->masterUom;
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    return Inertia::render('Product/New', compact('brands', 'mattypes', 'sites', 'masterUom', 'finishGoods'));
  }

  public function view(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    $masterUom = $this->masterUom;
    $InputData = $this->mergeWithDraft($request, $this->buildProductInput($request));
    return Inertia::render('Product/Detail', compact('InputData', 'brands', 'mattypes', 'sites', 'masterUom', 'finishGoods'));
  }

  public function create(ProductCreateRequest $request): RedirectResponse
  {
    $InputData = $this->buildProductInput($request);
    $this->persistDraft($request, $InputData);
    return Redirect::route('product.view', $InputData);
  }

  public function search(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    return Inertia::render('Product/Search', compact('brands', 'mattypes'));
  }

  public function searchBom(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $materials = $this->materials;
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
      'status'     => '',
    ];
    return Inertia::render('Product/SearchBom', compact('InputData', 'brands', 'mattypes', 'materials'));
  }

  public function edit(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    $masterUom = $this->masterUom;
    $InputData = $this->mergeWithDraft($request, $this->buildProductInput($request));
    $isDisabled = false;
    return Inertia::render('Product/Detail', compact('InputData', 'brands', 'mattypes', 'sites', 'masterUom', 'finishGoods', 'isDisabled'));
  }

  public function find(ProductSearchRequest $request): RedirectResponse
  {
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
    ];
    return Redirect::route('product.search.bom', $InputData);
  }

  public function findBom(ProductSearchBomRequest $request): RedirectResponse
  {
    $InputData = [
      'brand'        => $request->brand,
      'mattype'      => $request->mattype,
      'subMattype'   => $request->subMattype,
      'materialId'   => $request->materialId,
      'fgStatus'     => $request->fgStatus ?: 'INS',
      'bomId'        => 'B10SW00727_RJ_01',
      'bomDesc'      => 'Description of BOM ID',
      'finishGoods'  => '10BR',
      'fullDescEn'   => 'Test',
      'fullDescTh'   => 'ทดสอบ',
      'searchDesc'   => 'ทดสอบ ค้นหา',
      'site'         => '01',
      'uom'          => 'Z06',
    ];
    $InputData = $this->mergeWithDraft($request, $InputData);
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
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_MATID(:p_brand, :p_mattype, :p_sub_mattype, :p_suggest_material_id); END;');
    $stmt->bindParam(':p_brand', $validated['brand'], PDO::PARAM_STR);
    $stmt->bindParam(':p_mattype', $validated['mattype'], PDO::PARAM_STR);
    $stmt->bindParam(':p_sub_mattype', $validated['subMattype'], PDO::PARAM_STR);
    $stmt->bindParam(':p_suggest_material_id', $materialId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_MATID',
      'materialId' => $materialId,
    ]);
  }

  public function generateBomId(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'suggestId' => ['required'],
      'site' => ['required'],
    ]);

    $bomId = null;
    $pdo = DB::getPdo();
    $stmt = $pdo->prepare('BEGIN PROJ1_2_GEN_BOMID_FG(:p_suggest_id, :p_site, :p_out_bomid); END;');
    $stmt->bindParam(':p_suggest_id', $validated['suggestId'], PDO::PARAM_STR);
    $stmt->bindParam(':p_site', $validated['site'], PDO::PARAM_STR);
    $stmt->bindParam(':p_out_bomid', $bomId, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->execute();

    return response()->json([
      'program' => 'PROJ1_2_GEN_BOMID_FG',
      'bomId' => $bomId,
    ]);
  }

  public function update(ProductCreateRequest $request): RedirectResponse
  {
    $InputData = $this->buildProductInput($request);
    $this->persistDraft($request, $InputData);
    return Redirect::route('product.view', $InputData);
  }

  public function delete(Request $request): RedirectResponse
  {
    $draftKey = $this->draftKey($request->get('materialId'), $request->get('bomId'));
    if ($draftKey) {
      $request->session()->forget($draftKey);
    }

    return Redirect::route('product.search');
  }
}
