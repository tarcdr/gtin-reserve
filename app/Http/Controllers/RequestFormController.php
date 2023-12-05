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
use App\Models\Mattype;
use App\Models\TradingUnit;
use App\Models\Gtin;
use PDO;
use App\Exports\GtinExport;
use Maatwebsite\Excel\Facades\Excel;

class RequestFormController extends Controller
{
    public function __construct()
    {
        $this->host     = env('DB_HOST', '');
        $this->port     = env('DB_PORT', '');
        $this->database = env('DB_DATABASE', '');
        $this->username = env('DB_USERNAME', '');
        $this->password = env('DB_PASSWORD', '');

        $this->db = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=' . $this->host . ')(PORT=' . $this->port . '))(CONNECT_DATA=(SERVICE_NAME = ' . $this->database . ')))';
    }
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
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
          $conn = oci_connect($this->username, $this->password, $this->db);

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
        foreach (Mattype::all() as $m) {
          array_push($mattype, [
            "code" => $m->group_mat_type,
            "name" => $m->mat_type . ' - ' . $m->mat_type_description,
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

    /**
     * Update the user's profile information.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $p_gtin       = $request->gtin;
        $p_user_login = $request->user()->user_login;
        $conn = oci_connect($this->username, $this->password, $this->db);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $stid0 = oci_parse($conn, 'begin proj1_button_confirm(:p_gtin, :p_user_login); end;');
        oci_bind_by_name($stid0, ':p_gtin',       $p_gtin);
        oci_bind_by_name($stid0, ':p_user_login', $p_user_login);

        oci_execute($stid0);

        return Redirect::route('report');
    }

    public function report(Request $request): Response
    {
      $gtins = [];
      // array_push($gtins, [
      //   "material_id" => '10HO00001',
      //   "trading_unit" => 'Pcs',
      //   "global_trade_item_number" => '8859533401487',
      //   "user_last_update" => '',
      //   "last_update" => '',
      //   "status_gtin" => 'RESERVE',
      // ]);
      foreach (Gtin::orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')->orderBy('material_id')->get() as $m) {
        array_push($gtins, $m);
      }
      return Inertia::render('Report', [ "gtins" => $gtins ]);
    }

    public function export()
    {
        return Excel::download(new GtinExport, 'export.xlsx');
    }
}
