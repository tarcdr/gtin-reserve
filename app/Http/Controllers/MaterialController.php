<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\MaterialTemp;
use App\Models\Brand;
use App\Models\Mattype;
use App\Exports\MaterialTempExport;
use PDO;
use Maatwebsite\Excel\Facades\Excel;

class MaterialController extends Controller
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

        $p_brand      = $request->brand;
        $p_mattype    = $request->mattype;
        $p_last_id    = $request->p_last_id;
        $p_suggest_id = $request->p_suggest_id;
        $success      = NULL;

        foreach (Brand::all() as $b) {
          array_push($brand, [
            "abb"  => $b->brand_abb,
            "code" => $b->brand,
          ]);
        }
        foreach (Mattype::all() as $m) {
          array_push($mattype, [
            "code" => $m->mat_type,
            "name" => $m->mat_type . ' - ' . $m->mat_type_description,
          ]);
        }

        if ($request->brand && $request->mattype) {
          $conn = oci_connect($this->username, $this->password, $this->db);

          if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
          }

          $stid = oci_parse($conn, 'begin proj1_gen_matid(:p_brand, :p_mattype, :p_last_id, :p_suggest_id); end;');
          oci_bind_by_name($stid, ':p_brand',      $p_brand);
          oci_bind_by_name($stid, ':p_mattype',    $p_mattype);
          oci_bind_by_name($stid, ':p_last_id',    $p_last_id,    100);
          oci_bind_by_name($stid, ':p_suggest_id', $p_suggest_id, 100);

          oci_execute($stid);

          $p_material_id      = NULL;
          $p_material_desc    = $request->material_desc;
          $p_user_login       = $request->user()->user_login;
          if ($request->materialChoose === 'l') {
            $p_material_id = $p_last_id;
          } else if ($request->materialChoose === 's') {
            $p_material_id = $p_suggest_id;
          }
          if ($p_material_id) {
            if (!$conn) {
                $e = oci_error();
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
  
            $stid_exc = oci_parse($conn, 'begin proj1_button_reserve_matid(:p_material_id, :p_material_desc, :p_brand, :p_mattype, :p_user_login); end;');
            oci_bind_by_name($stid_exc, ':p_material_id',   $p_material_id);
            oci_bind_by_name($stid_exc, ':p_material_desc', $p_material_desc);
            oci_bind_by_name($stid_exc, ':p_brand',         $p_brand);
            oci_bind_by_name($stid_exc, ':p_mattype',       $p_mattype);
            oci_bind_by_name($stid_exc, ':p_user_login',    $p_user_login);

            oci_execute($stid_exc);

            $success = true;
          }
        }

        return Inertia::render('Material/Request', [
          'InputData' => [
            'brand'               => $p_brand,
            'mattype'             => $p_mattype,
            'material_desc'       => $request->material_desc,
            'p_last_id'           => $p_last_id,
            'p_suggest_id'        => $p_suggest_id,
            'materialChoose'      => $request->materialChoose,
            'success'             => $success,
          ],
          'brand'        => $brand,
          "mattype"      => $mattype,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(MaterialRequest $request): RedirectResponse
    {
        $inputData = [
          'brand'               => $request->brand,
          'mattype'             => $request->mattype,
          'material_desc'       => $request->material_desc,
          'p_last_id'           => $request->p_last_id,
          'p_suggest_id'        => $request->p_suggest_id,
          'materialChoose'      => $request->materialChoose,
        ];
        return Redirect::route('material.request', $inputData);
    }

    /**
     * Update the user's profile information.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $p_material_id = $request->material_id;
        $p_user_login  = $request->user()->user_login;
        $conn = oci_connect($this->username, $this->password, $this->db);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $stid0 = oci_parse($conn, 'begin proj1_button_confirm_matid(:p_material_id, :p_user_login); end;');
        oci_bind_by_name($stid0, ':p_material_id',  $p_material_id);
        oci_bind_by_name($stid0, ':p_user_login',   $p_user_login);

        oci_execute($stid0);

        return Redirect::route('material.report');
    }

    public function report(Request $request): Response
    {
      $materials = [];
      $search = $request->search;
      if ($search) {
        foreach (MaterialTemp::where('material_id', 'like', $search . '%')->orderByRaw('(case when status = \'RESERVE\' then 0 else 1 end) asc')->orderBy('last_update', 'desc')->get() as $m) {
          array_push($materials, $m);
        }
      } else {
        foreach (MaterialTemp::orderByRaw('(case when status = \'RESERVE\' then 0 else 1 end) asc')->orderBy('last_update', 'desc')->get() as $m) {
          array_push($materials, $m);
        }
      }
      return Inertia::render('Material/Report', [ "materials" => $materials ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $inputData = [
          'search' => $request->search,
        ];
        return Redirect::route('material.report', $inputData);
    }

    public function export()
    {
        return Excel::download(new MaterialTempExport, 'material_export.xlsx');
    }
}
