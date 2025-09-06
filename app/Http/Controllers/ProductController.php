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

class ProductController extends Controller
{
  protected $brands;
  protected $materials;
  protected $masterUom;
  protected $masterSite;
  protected MasterCatLookup $mk;

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
        "code"  => "001",
        "label" => "001"
      ], [
        "code"  => "002",
        "label" => "002"
      ]
    ];
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

  public function new(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $masterUom = $this->masterUom;
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    return Inertia::render('NewProduct', compact('brands', 'mattypes', 'sites', 'masterUom', 'finishGoods'));
  }

  public function view(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $materials = $this->materials;
    $sites = $this->masterSite;
    $finishGoods = $this->mk->subcategoriesOf('10');
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
      'materialId' => $request->materialId,
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
      'finishGoods'  => '10BR',
      'fullDescEn'   => 'Test',
      'fullDescTh'   => 'ทดสอบ',
      'searchDesc'   => 'ทดสอบ ค้นหา',
      'productGroup' => 'AT HOME',
      'site'         => '01',
      'uom'          => 'Z06',
    ];
    return Inertia::render('ProductDetail', compact('InputData', 'brands', 'mattypes', 'sites', 'materials', 'finishGoods'));
  }

  public function create(ProductCreateRequest $request): RedirectResponse
  {
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
    ];
    return Redirect::route('product.edit', $InputData);
  }

  public function search(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    return Inertia::render('ProductSearch', compact('brands', 'mattypes'));
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
      'status'     => 'INS',
    ];
    return Inertia::render('ProductSearchBom', compact('InputData', 'brands', 'mattypes', 'materials'));
  }

  public function edit(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    return Inertia::render('ProductSearch', compact('brands', 'mattypes'));
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
      'materialId'   => $request->materialId['code'],
      'bomId'        => 'B10SW00727_RJ_01',
      'bomDesc'      => 'Description of BOM ID',
      'finishGoods'  => '10BR',
      'fullDescEn'   => 'Test',
      'fullDescTh'   => 'ทดสอบ',
      'searchDesc'   => 'ทดสอบ ค้นหา',
      'productGroup' => 'AT HOME',
      'site'         => '01',
      'uom'          => 'Z06',
    ];
    return Redirect::route('product.view', $InputData);
  }
}
