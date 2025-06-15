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

class ProductController extends Controller
{
  protected $brands;
  protected $materials;

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

  public function new(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = self::$sites;
    return Inertia::render('NewProduct', compact('brands', 'mattypes', 'sites'));
  }

  public function view(Request $request): Response
  {
    $brands = $this->brands;
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] != '5'));
    $sites = self::$sites;
    $materials = $this->materials;
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
      'materialId' => $request->materialId,
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
    ];
    return Inertia::render('ProductDetail', compact('InputData', 'brands', 'mattypes', 'sites', 'materials'));
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
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
      'materialId' => $request->materialId['code'],
      'bomId'      => 'B10SW00727_RJ_01',
      'bomDesc'    => 'Description of BOM ID',
    ];
    return Redirect::route('product.view', $InputData);
  }
}
