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
use PDO;

class RequestFormController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
        $isMock = false;
        $p_brand_code   = $request->brand;
        $p_mattype_code = $request->mattype;
        $host = env('DB_HOST', '');
        $database = env('DB_DATABASE', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');

        $brand = [];
        $mattype = [];
        $materials = [];

        if (!$isMock) {
          foreach (Material::select('brand')->whereNotNull('brand')->groupBy('brand')->get() as $b) {
            array_push($brand, [
              "code" => $b->brand
            ]);
          }
          foreach (Material::select('mat_type')->whereNotNull('mat_type')->groupBy('mat_type')->get() as $m) {
            array_push($mattype, [
              "code" => $m->mat_type
            ]);
          }
          if ($request->brand !== '' && $request->mattype !== '') {
            foreach (Material::where('brand', $request->brand)->where('mat_type', $request->mattype)->get() as $m) {
              array_push($materials, [
                "brand"       => $m->brand,
                "mat_type"    => $m->mat_type,
                "material_id" => $m->material_id,
              ]);
            }
          }
        } else {
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
        }

        return Inertia::render('Request', [
          'InputData' => [
            'brand'              => $request->brand,
            'mattype'            => $request->mattype,
            'gtinExist'          => $request->gtinExist,
            'gtinCode'           => '',
            'gtinForPcs'         => '',
            'gtinForInnerOrPack' => '',
            'gtinCodePcs'        => 'XXXXXXX'
          ],
          'brand' => $brand,
          "mattype" => $mattype,
          "materials" => $materials,
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
          'gtinCode'           => $request->gtinCode,
          'gtinForPcs'         => $request->gtinForPcs,
          'gtinForInnerOrPack' => $request->gtinForInnerOrPack,
        ];
        return Redirect::route('request', $inputData);
    }
}
