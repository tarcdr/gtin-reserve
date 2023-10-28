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

class RequestFormController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
        $brand = [];
        foreach (Brand::all() as $b) {
          array_push($brand, [
            "code" => $b->brand_abb,
            "name" => $b->brand_name,
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
            'brand' => $request->brand,
            'mattype' => $request->mattype,
            'gtinExist' => '',
            'company' => '',
            'gtinCode' => '',
            'gtinForPcs' => '',
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
        $p_brand_name   = null;
        $p_mattype_name = null;
        $p_brand_abb    = null;
        $l_product_code = null;
        $s_product_code = null;
        $p_msg          = null;

        $inputData = [
          'brand' => $p_brand_code,
          'mattype' => $p_mattype_code,
          'gtinExist' => false,
          'company' => '',
          'gtinCode' => '',
          'gtinForPcs' => '',
          'gtinForInnerOrPack' => '',
        ];

        $stmt = $pdo->prepare("begin proj1_gen_mattype(:p_brand_code, :p_mattype_code, :p_brand_name, :p_mattype_name, :p_brand_abb, :l_product_code, :s_product_code, :p_msg); end;");
        $stmt->bindParam(':p_brand_code', $p_brand_code, PDO::PARAM_STR);
        $stmt->bindParam(':p_mattype_code', $p_mattype_code, PDO::PARAM_STR);
        $stmt->bindParam(':p_brand_name', $p_brand_name, PDO::PARAM_STR);
        $stmt->bindParam(':p_mattype_name', $p_mattype_name, PDO::PARAM_STR);
        $stmt->bindParam(':p_brand_abb', $p_brand_abb, PDO::PARAM_STR);
        $stmt->bindParam(':l_product_code', $l_product_code, PDO::PARAM_STR);
        $stmt->bindParam(':s_product_code', $s_product_code, PDO::PARAM_STR);
        $stmt->bindParam(':p_msg', $p_msg, PDO::PARAM_STR);
        $stmt->execute();

        return Redirect::route('request', [$inputData]);
    }
}
