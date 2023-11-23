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
        $isMock = true;
        $p_brand_code   = $request->brand;
        $p_mattype_code = $request->mattype;
        $host = env('DB_HOST', '');
        $database = env('DB_DATABASE', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');

        $brand = [];
        $mattype = [];
        $products = [];

        if (!$isMock) {
          $conn = oci_connect($username, $password, $host . '/' . $database);
          if (!$conn) {
              $e = oci_error();
              trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
          }
  
          $stid = oci_parse($conn, 'begin proj1_gen_mattype(:p_brand_code, :p_mattype_code, :p_brand_name, :p_mattype_name, :p_brand_abb, :l_product_code, :s_product_code, :p_msg); end;');
          oci_bind_by_name($stid, ':p_brand_code',   $p_brand_code);
          oci_bind_by_name($stid, ':p_mattype_code', $p_mattype_code);
          oci_bind_by_name($stid, ':p_brand_name',   $p_brand_name, 100);
          oci_bind_by_name($stid, ':p_mattype_name', $p_mattype_name, 100);
          oci_bind_by_name($stid, ':p_brand_abb',    $p_brand_abb, 100);
          oci_bind_by_name($stid, ':l_product_code', $l_product_code, 100);
          oci_bind_by_name($stid, ':s_product_code', $s_product_code, 100);
          oci_bind_by_name($stid, ':p_msg',          $p_msg, 100);
  
          oci_execute($stid);

          foreach (Brand::all() as $b) {
            array_push($brand, [
              "code" => $b->brand_code,
              "name" => $b->brand_name,
              "abb"  => $b->brand_abb,
            ]);
          }
          foreach (Mattype::all() as $m) {
            array_push($mattype, [
              "code" => $m->mattype_code,
              "name" => $m->mattype_name,
            ]);
          }
        } else {
          $l_product_code = '';
          $s_product_code = '';
          $p_msg = '';

          $brand = [[
            "abb" => "HO",
            "code" => "HO",
            "name" => "AT HOME"
          ], [
            "abb" => "BR",
            "code" => "BR",
            "name" => "BEAR"
          ], [
            "abb" => "BS",
            "code" => "BS",
            "name" => "BISSELL"
          ], [
            "abb" => "EJ",
            "code" => "EJ",
            "name" => "EMJOI"
          ], [
            "abb" => "FS",
            "code" => "FS",
            "name" => "FLYCO"
          ], [
            "abb" => "FF",
            "code" => "FF",
            "name" => "FORFUN"
          ], [
            "abb" => "IN",
            "code" => "IN",
            "name" => "IONIE"
          ], [
            "abb" => "JS",
            "code" => "JS",
            "name" => "JASON"
          ], [
            "abb" => "KR",
            "code" => "KR",
            "name" => "KURON"
          ], [
            "abb" => "LS",
            "code" => "LS",
            "name" => "LESASHA"
          ], [
            "abb" => "NP",
            "code" => "NP",
            "name" => "N LifePlus"
          ], [
            "abb" => "NL",
            "code" => "NL",
            "name" => "NAMU LIFE"
          ], [
            "abb" => "SW",
            "code" => "SW",
            "name" => "NAMU LIFE SNAILWHITE"
          ], [
            "abb" => "NN",
            "code" => "NN",
            "name" => "NAMULIFE NATURALS"
          ], [
            "abb" => "OX",
            "code" => "OX",
            "name" => "OXE'CURE"
          ], [
            "abb" => "PF",
            "code" => "PF",
            "name" => "PRETTIIFACE"
          ], [
            "abb" => "SS",
            "code" => "SS",
            "name" => "SMOOTH SKIN-IPL"
          ], [
            "abb" => "SO",
            "code" => "SO",
            "name" => "SOS"
          ], [
            "abb" => "SK",
            "code" => "SK",
            "name" => "SPARKLE"
          ], [
            "abb" => "UP",
            "code" => "UP",
            "name" => "UP 5"
          ], [
            "abb" => "VT",
            "code" => "VT",
            "name" => "VITAINNO"
          ], [
            "abb" => "YR",
            "code" => "YR",
            "name" => "YOURS"
          ], [
            "abb" => "IL",
            "code" => "IL",
            "name" => "I-Life"
          ], [
            "abb" => "MV",
            "code" => "MV",
            "name" => "Makavelic"
          ]];
          $mattype = [[
              "code" => "10",
              "name" => "FG (Own brand)"
            ], [
              "code" => "11",
              "name" => "FG (OEM)"
            ], [
              "code" => "12",
              "name" => "FG (Set)"
            ], [
              "code" => "13",
              "name" => "FG (FOC)"
            ], [
              "code" => "20",
              "name" => "Semi FG Lv2 (ผลิตเอง)"
            ], [
              "code" => "21",
              "name" => "Semi FG Lv2 (ซื้อในประเทศ)"
            ], [
              "code" => "22",
              "name" => "Semi FG Lv2 (ซื้อต่างประเทศ)"
            ], [
              "code" => "30",
              "name" => "Semi FG Lv1 (ผลิตเอง)"
            ], [
              "code" => "31",
              "name" => "Semi FG Lv1 (ซื้อในประเทศ)"
          ]];
          $company = [[
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
          ]];
        }

        return Inertia::render('Request', [
          'InputData' => [
            'brand'              => $request->brand,
            'mattype'            => $request->mattype,
            'gtinExist'          => $request->gtinExist,
            'company'            => '',
            'gtinCode'           => '',
            'gtinForPcs'         => '',
            'gtinForInnerOrPack' => '',
            'l_product_code'     => $l_product_code,
            's_product_code'     => $s_product_code,
            'msg'                => $p_msg,
            'gtinCodePcs'        => 'XXXXXXX'
          ],
          'brand' => $brand,
          "mattype" => $mattype,
          "company" => $company,
          "products" => $products,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(FormUpdateRequest $request): RedirectResponse
    {
        $inputData = [
          'brand'              => $request->brand,
          'mattype'            => $request->mattype,
          'gtinExist'          => $request->gtinExist,
          'company'            => $request->company,
          'gtinCode'           => $request->gtinCode,
          'gtinForPcs'         => $request->gtinForPcs,
          'gtinForInnerOrPack' => $request->gtinForInnerOrPack,
        ];
        return Redirect::route('request', $inputData);
    }
}
