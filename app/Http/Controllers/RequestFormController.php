<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;
use App\Models\Mattype;
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

        $conn = oci_connect($username, $password, $host . '/' . $database);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        
        $p1 = 8;
        
        $stid = oci_parse($conn, 'begin program2(:p1, :p2); end;');
        oci_bind_by_name($stid, ':p1', $p1);
        oci_bind_by_name($stid, ':p2', $p2, 40);
        
        oci_execute($stid);

        $brand = [];
        foreach (Brand::all() as $b) {
          array_push($brand, [
            "code" => $b->brand_code,
            "name" => $b->brand_name,
            "abb"  => $b->brand_abb,
          ]);
        }
        $mattype = [];
        foreach (Mattype::all() as $m) {
          array_push($mattype, [
            "code" => $m->mattype_code,
            "name" => $m->mattype_name,
          ]);
        }

        return Inertia::render('Request', [
          'InputData' => [
            'brand'              => $request->brand,
            'brand_abb'          => $request->brand_abb,
            'mattype'            => $request->mattype,
            'gtinExist'          => $request->gtinExist,
            'company'            => '',
            'gtinCode'           => '',
            'gtinForPcs'         => '',
            'gtinForInnerOrPack' => '',
          ],
          'brand' => $brand,
          "mattype" => $mattype,
          "company" => [[
              "code" => "8859082",
              "name" => "DDD & NLP"
            ], [
              "code" => "8858690",
              "name" => "KUR"
            ], [
              "code" => "8859510",
              "name" => "DDM"
            ], [
              "code" => "8859533",
              "name" => "SMS"
          ]],
          "exists" => [[
            "code" => "Y",
            "name" => "Yes"
          ], [
            "code" => "N",
            "name" => "No"
          ]]
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(FormUpdateRequest $request): RedirectResponse
    {
        $p_brand_code   = $request->brand;
        $p_mattype_code = $request->mattype;
        $p_brand_name   = '';
        $p_mattype_name = '';
        $p_brand_abb    = '';
        $l_product_code = '';
        $s_product_code = '';
        $p_msg          = '';

        $host = env('DB_HOST', '');
        $database = env('DB_DATABASE', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');

        $conn = oci_connect($username, $password, $host . '/' . $database);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        
        $stid = oci_parse($conn, 'begin proj1_gen_mattype(:p_brand_code, :p_mattype_code, :p_brand_name, :p_mattype_name, :p_brand_abb, :l_product_code, :s_product_code, :p_msg); end;');
        oci_bind_by_name($stid, ':p_brand_code',   $p_brand_code);
        oci_bind_by_name($stid, ':p_mattype_code', $p_mattype_code);
        oci_bind_by_name($stid, ':p_brand_name',   $p_brand_name);
        oci_bind_by_name($stid, ':p_mattype_name', $p_mattype_name);
        oci_bind_by_name($stid, ':p_brand_abb',    $p_brand_abb);
        oci_bind_by_name($stid, ':l_product_code', $l_product_code);
        oci_bind_by_name($stid, ':s_product_code', $s_product_code);
        oci_bind_by_name($stid, ':p_msg',          $p_msg);
        
        oci_execute($stid);

        $inputData = [
          'brand'              => $request->brand,
          'brand_abb'          => $request->brand_abb,
          'mattype'            => $request->mattype,
          'gtinExist'          => $request->gtinExist,
          'company'            => '',
          'gtinCode'           => '',
          'gtinForPcs'         => '',
          'gtinForInnerOrPack' => '',
          'l_product_code'     => $l_product_code,
          's_product_code'     => $s_product_code,
          'msg'                => $p_msg,
        ];

        return Redirect::route('request', $inputData);
    }
}
