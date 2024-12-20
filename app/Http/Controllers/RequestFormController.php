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

        $p_message_pcs       = NULL;
        $p_message_box       = NULL;
        $p_message           = NULL;
        $p_new_last_gtin_pcs = $request->p_new_last_gtin_pcs;
        $p_suggest_gtin_pcs  = $request->p_suggest_gtin_pcs;
        $p_new_last_gtin_box = $request->p_new_last_gtin_box;
        $p_suggest_gtin_box  = $request->p_suggest_gtin_box;
        $p_gtinPcsChoose     = $request->gtinPcsChoose;
        $p_gtinPackChoose    = $request->gtinPackChoose;

        if ($request->material_id) {
          $conn = oci_connect($this->username, $this->password, $this->db, 'AL32UTF8');

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
          $checkPcs = Gtin::where('material_id', $request->material_id)->where('typ_gtin', '1')->get();
          if (count($checkPcs) > 0) {
            $p_gtin_pcs = '';
          }
          if ($request->gtinExistPack && $request->gtinPackCode) {
            $p_gtin_box = $request->gtinPackCode;
          } else if ($request->gtinPackChoose === 'l') {
            $p_gtin_box = $p_new_last_gtin_box;
          } else if ($request->gtinPackChoose === 's') {
            $p_gtin_box = $p_suggest_gtin_box;
          }
          $checkPack = Gtin::where('material_id', $request->material_id)->where('typ_gtin', '2')->where('trading_unit', $p_trading_unit_box)->get();
          if (count($checkPack) > 0) {
            $p_gtin_box = '';
          }
          if ($p_gtin_pcs || $p_gtin_box) {
            if (!$conn) {
                $e = oci_error();
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
  
            $stid0 = oci_parse($conn, 'begin proj1_button_reserve(:p_material_id, :p_trading_unit_pcs, :p_trading_unit_box, :p_gtin_pcs, :p_gtin_box, :p_user_login, :p_message_pcs, :p_message_box, :p_message); end;');
            oci_bind_by_name($stid0, ':p_material_id',      $p_material_id);
            oci_bind_by_name($stid0, ':p_trading_unit_pcs', $p_trading_unit_pcs);
            oci_bind_by_name($stid0, ':p_trading_unit_box', $p_trading_unit_box);
            oci_bind_by_name($stid0, ':p_gtin_pcs',         $p_gtin_pcs);
            oci_bind_by_name($stid0, ':p_gtin_box',         $p_gtin_box);
            oci_bind_by_name($stid0, ':p_user_login',       $p_user_login);
            oci_bind_by_name($stid0, ':p_message_pcs',      $p_message_pcs, 100);
            oci_bind_by_name($stid0, ':p_message_box',      $p_message_box, 100);
            oci_bind_by_name($stid0, ':p_message',          $p_message, 100);

            oci_execute($stid0);

            $p_gtinPcsChoose  = '';
            $p_gtinPackChoose = '';
          }

          foreach (Gtin::where('material_id', $request->material_id)->orderBy('global_trade_item_number')->get() as $m) {
            array_push($gtins, $m);
          }
          if (!$conn) {
              $e = oci_error();
              trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
          }

          $stid = oci_parse($conn, 'begin proj1_find_newgtin(:p_new_last_gtin_pcs, :p_suggest_gtin_pcs, :p_new_last_gtin_box, :p_suggest_gtin_box, :p_material_id); end;');
          oci_bind_by_name($stid, ':p_new_last_gtin_pcs', $p_new_last_gtin_pcs, 100);
          oci_bind_by_name($stid, ':p_suggest_gtin_pcs',  $p_suggest_gtin_pcs,  100);
          oci_bind_by_name($stid, ':p_new_last_gtin_box', $p_new_last_gtin_box, 100);
          oci_bind_by_name($stid, ':p_suggest_gtin_box',  $p_suggest_gtin_box,  100);
          oci_bind_by_name($stid, ':p_material_id',       $p_material_id);
  
          oci_execute($stid);
        }

        foreach (Material::select('brand')->whereNotNull('brand')->groupBy('brand')->orderBy('brand')->get() as $b) {
          array_push($brand, [
            "code" => $b->brand
          ]);
        }
        foreach (Mattype::all() as $m) {
          array_push($mattype, [
            "code" => $m->mat_type,
            "name" => $m->mat_type . ' - ' . $m->mat_type_description,
          ]);
        }
        foreach (Material::select('material_id')->where('brand', $request->brand)->where('mattype_full', $request->mattype)->groupBy('material_id')->orderBy('material_id')->get() as $m) {
          array_push($materials, [
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
            'gtinPcsChoose'       => $p_gtinPcsChoose,
            'gtinPackCode'        => $request->gtinPackCode,
            'gtinPackChoose'      => $p_gtinPackChoose,
            'trading_unit'        => $request->trading_unit,
            "p_new_last_gtin_pcs" => $p_new_last_gtin_pcs,
            "p_suggest_gtin_pcs"  => $p_suggest_gtin_pcs,
            "p_new_last_gtin_box" => $p_new_last_gtin_box,
            "p_suggest_gtin_box"  => $p_suggest_gtin_box,
          ],
          'brand'         => $brand,
          "mattype"       => $mattype,
          "materials"     => $materials,
          "tradingUnits"  => $tradingUnits,
          "gtins"         => $gtins,
          "p_message_pcs" => $p_message_pcs,
          "p_message_box" => $p_message_box,
          "p_message"     => $p_message,
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
      $search = $request->search;
      if ($search) {
        foreach (Gtin::where('material_id', 'like', $search . '%')->orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')->orderByRaw('(case when last_update is null then to_date(\'1900-01-01\', \'YYYY-MM-DD\') else last_update end) desc')->get() as $m) {
          array_push($gtins, $m);
        }
      } else {
        foreach (Gtin::orderByRaw('(case when status_gtin = \'RESERVE\' then 0 else 1 end) asc')->orderByRaw('(case when last_update is null then to_date(\'1900-01-01\', \'YYYY-MM-DD\') else last_update end) desc')->get() as $m) {
          array_push($gtins, $m);
        }
      }
      return Inertia::render('Report', [ "gtins" => $gtins, "search" => $search, ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $inputData = [
          'search' => $request->search,
        ];
        return Redirect::route('report', $inputData);
    }

    public function export()
    {
        return Excel::download(new GtinExport, 'export.xlsx');
    }
}
