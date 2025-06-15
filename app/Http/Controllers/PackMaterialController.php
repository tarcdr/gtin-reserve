<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PackMaterialCreateRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;

class PackMaterialController extends Controller
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

  static $subMattypes = [[
    "code" => "0",
    "label" => "0"
  ], [
    "code" => "1",
    "label" => "1"
  ]];

  public function new(Request $request): Response
  {
    $mattypes = array_values(array_filter(self::$mattypes, fn($v) => $v['code'] == '5'));
    $subMattypes = self::$subMattypes;
    $InputData = [
      "mattype"    => "5",
      "subMattype" => "1",
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
    ];

    return Inertia::render('PackMaterial/New', compact('InputData', 'mattypes', 'subMattypes'));
  }

  public function create(PackMaterialCreateRequest $request): RedirectResponse
  {
    $InputData = [
      'brand'      => $request->brand,
      'mattype'    => $request->mattype,
      'subMattype' => $request->subMattype,
    ];
    return Redirect::route('product.edit', $InputData);
  }

  public function callNew(Request $request): RedirectResponse
  {
    $InputData = [
      'bomId'      => $request->bomId,
      'bomDesc'    => $request->bomDesc,
    ];
    return Redirect::route('packmaterial.new', $InputData);
  }
}
