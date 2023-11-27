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
        $host     = env('DB_HOST', '');
        $port     = env('DB_PORT', '');
        $database = env('DB_DATABASE', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');

        $brand = [];
        $mattype = [];
        $materials = [];
        $tradingUnits = [];
        $gtins = [];

        $p_new_last_gtin_pcs = $request->p_new_last_gtin_pcs;
        $p_suggest_gtin_pcs  = $request->p_suggest_gtin_pcs;
        $p_new_last_gtin_box = $request->p_new_last_gtin_box;
        $p_suggest_gtin_box  = $request->p_suggest_gtin_box;

        if ($request->material_id) {
          $db = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=' . $host . ')(PORT=' . $port . '))(CONNECT_DATA=(SERVICE_NAME = ' . $database . ')))';
          $conn = oci_connect($username, $password, $db);

          $p_material_id      = $request->material_id;
          $p_trading_unit_pcs = 'Pcs';
          $p_trading_unit_box = $request->trading_unit;
          $p_gtin_pcs         = NULL;
          $p_gtin_box         = NULL;
          $p_user_login       = $request->user()->user_login;
          if ($request->gtinExistPcs && $request->gtinPcsCode) {
            $p_gtin_pcs = $request->gtinPcsCode;
          } else if ($request->gtinPcsChoose === 'l') {
            $p_gtin_pcs = $p_new_last_gtin_pcs;
          } else if ($request->gtinPcsChoose === 's') {
            $p_gtin_pcs = $p_suggest_gtin_pcs;
          }
          if ($request->gtinExistPack && $request->gtinPackCode) {
            $p_gtin_box = $request->gtinPackCode;
          } else if ($request->gtinPackChoose === 'l') {
            $p_gtin_box = $p_new_last_gtin_box;
          } else if ($request->gtinPackChoose === 's') {
            $p_gtin_box = $p_suggest_gtin_box;
          }
          if ($p_gtin_pcs || $p_gtin_box) {
            if (!$conn) {
                $e = oci_error();
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
  
            $stid0 = oci_parse($conn, 'begin proj1_button_reserve(:p_material_id, :p_trading_unit_pcs, :p_trading_unit_box, :p_gtin_pcs, :p_gtin_box, :p_user_login); end;');
            oci_bind_by_name($stid0, ':p_material_id',      $p_material_id);
            oci_bind_by_name($stid0, ':p_trading_unit_pcs', $p_trading_unit_pcs);
            oci_bind_by_name($stid0, ':p_trading_unit_box', $p_trading_unit_box);
            oci_bind_by_name($stid0, ':p_gtin_pcs',         $p_gtin_pcs);
            oci_bind_by_name($stid0, ':p_gtin_box',         $p_gtin_box);
            oci_bind_by_name($stid0, ':p_user_login',       $p_user_login);
    
            oci_execute($stid0);
          }

          foreach (Gtin::where('material_id', $request->material_id)->orderBy('global_trade_item_number')->get() as $m) {
            array_push($gtins, $m);
          }
          if (!$conn) {
              $e = oci_error();
              trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
          }

          $stid = oci_parse($conn, 'begin proj1_find_newgtin(:p_new_last_gtin_pcs, :p_suggest_gtin_pcs, :p_new_last_gtin_box, :p_suggest_gtin_box); end;');
          oci_bind_by_name($stid, ':p_new_last_gtin_pcs', $p_new_last_gtin_pcs, 100);
          oci_bind_by_name($stid, ':p_suggest_gtin_pcs',  $p_suggest_gtin_pcs,  100);
          oci_bind_by_name($stid, ':p_new_last_gtin_box', $p_new_last_gtin_box, 100);
          oci_bind_by_name($stid, ':p_suggest_gtin_box',  $p_suggest_gtin_box,  100);
  
          oci_execute($stid);
        }

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

        return Inertia::render('Request', [
          'InputData' => [
            'brand'               => $request->brand,
            'mattype'             => $request->mattype,
            'material_id'         => $request->material_id,
            'gtinExistPcs'        => $request->gtinExistPcs,
            'gtinExistPack'       => $request->gtinExistPack,
            'gtinPcsCode'         => $request->gtinPcsCode,
            'gtinPcsChoose'       => $request->gtinPcsChoose,
            'gtinPackCode'        => $request->gtinPackCode,
            'gtinPackChoose'      => $request->gtinPackChoose,
            'trading_unit'        => $request->trading_unit,
            "p_new_last_gtin_pcs" => $p_new_last_gtin_pcs,
            "p_suggest_gtin_pcs"  => $p_suggest_gtin_pcs,
            "p_new_last_gtin_box" => $p_new_last_gtin_box,
            "p_suggest_gtin_box"  => $p_suggest_gtin_box,
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
          'brand'               => $request->brand,
          'mattype'             => $request->mattype,
          'material_id'         => $request->material_id,
          'gtinExistPcs'        => $request->gtinExistPcs,
          'gtinExistPack'       => $request->gtinExistPack,
          'gtinPcsCode'         => $request->gtinPcsCode,
          'gtinPcsChoose'       => $request->gtinPcsChoose,
          'gtinPackCode'        => $request->gtinPackCode,
          'gtinPackChoose'      => $request->gtinPackChoose,
          'trading_unit'        => $request->trading_unit,
          "p_new_last_gtin_pcs" => $request->p_new_last_gtin_pcs,
          "p_suggest_gtin_pcs"  => $request->p_suggest_gtin_pcs,
          "p_new_last_gtin_box" => $request->p_new_last_gtin_box,
          "p_suggest_gtin_box"  => $request->p_suggest_gtin_box,
        ];
        return Redirect::route('request', $inputData);
    }

    public function report(Request $request): Response
    {
      $gtins = [];
      foreach (Gtin::orderBy('material_id')->orderBy('last_update', 'desc')->orderBy('typ_gtin')->orderBy('global_trade_item_number')->get() as $m) {
        array_push($gtins, $m);
      }
      return Inertia::render('Report', [ "gtins" => $gtins ]);
    }
}
