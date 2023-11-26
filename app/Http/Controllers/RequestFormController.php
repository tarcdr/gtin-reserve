<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\TradingUnit;
use App\Models\Gtin;
use PDO;

class RequestFormController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
        $host = env('DB_HOST', '');
        $database = env('DB_DATABASE', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');

        $brand = [];
        $mattype = [];
        $materials = [];
        $tradingUnits = [];
        $gtins = [];

        foreach (Material::select('brand')->whereNotNull('brand')->groupBy('brand')->orderBy('brand')->get() as $b) {
          array_push($brand, [
            "code" => $b->brand
          ]);
        }
        foreach (Material::select('mat_type')->whereNotNull('mat_type')->groupBy('mat_type')->orderBy('mat_type')->get() as $m) {
          array_push($mattype, [
            "code" => $m->mat_type
          ]);
        }
        foreach (Material::where('brand', $request->brand)->where('mat_type', $request->mattype)->orderBy('material_id')->get() as $m) {
          array_push($materials, [
            "brand"       => $m->brand,
            "mat_type"    => $m->mat_type,
            "material_id" => $m->material_id,
          ]);
        }
        foreach (TradingUnit::where('type_gtin_unit', 2)->orderBy('trading_unit')->get() as $m) {
          array_push($tradingUnits, [
            "unit" => $m->trading_unit,
            "type" => $m->type_gtin_unit,
          ]);
        }
        if ($request->material_id) {
          foreach (Gtin::where('material_id', $request->material_id)->orderBy('global_trade_item_number')->get() as $m) {
            array_push($tradingUnits, $m);
          }
        }

        return Inertia::render('Request', [
          'InputData' => [
            'brand'         => $request->brand,
            'mattype'       => $request->mattype,
            'material_id'   => $request->material_id,
            'gtinExistPcs'  => $request->gtinExistPcs,
            'gtinExistPack' => $request->gtinExistPack,
            'gtinCodePcs'   => $request->gtinCodePcs,
            'gtinPcsCode'   => $request->gtinPcsCode,
            'gtinCodePack'  => $request->gtinCodePack,
            'gtinPackCode'  => $request->gtinPackCode,
          ],
          'brand'        => $brand,
          "mattype"      => $mattype,
          "materials"    => $materials,
          "tradingUnits" => $tradingUnits,
          "gtins"        => $gtins,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(FormUpdateRequest $request): RedirectResponse
    {
        $inputData = [
          'brand'         => $request->brand,
          'mattype'       => $request->mattype,
          'material_id'   => $request->material_id,
          'gtinExistPcs'  => $request->gtinExistPcs,
          'gtinExistPack' => $request->gtinExistPack,
          'gtinCodePcs'   => $request->gtinCodePcs,
          'gtinPcsCode'   => $request->gtinPcsCode,
          'gtinCodePack'  => $request->gtinCodePack,
          'gtinPackCode'  => $request->gtinPackCode,
        ];
        return Redirect::route('request', $inputData);
    }
}
