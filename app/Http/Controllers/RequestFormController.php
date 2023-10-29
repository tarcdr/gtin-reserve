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
        $p_brand_name   = 'NULL';
        $p_mattype_name = 'NULL';
        $p_brand_abb    = 'NULL';
        $l_product_code = 'NULL';
        $s_product_code = 'NULL';
        $p_msg          = '""';

        $input = 20;
        $output = '';
        $procedureName = 'get_install';

        $bindings = [
            'p_out' => [
                'value' => &$output,
                'type'  => PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT,
            ],
        ];

        DB::executeProcedure($procedureName, $bindings);

        $inputData = [
          'brand' => $p_brand_code,
          'mattype' => $p_mattype_code,
          'gtinExist' => false,
          'company' => '',
          'gtinCode' => '',
          'gtinForPcs' => '',
          'gtinForInnerOrPack' => '',
          'msg' => $p_msg,
        ];

        return Redirect::route('request', [$inputData]);
    }
}
