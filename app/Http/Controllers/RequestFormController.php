<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Brand;

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
            "code" => $b->BRAND_ABB,
            "name" => $b->BRAND_NAME,
          ]);
        }

        return Inertia::render('Request', [
          'InputData' => [
            'brand' => '',
            'mattype' => '',
            'gtinExist' => false,
            'company' => '',
            'gtinCode' => '',
            'gtinForPcs' => '',
            'gtinForInnerOrPack' => '',
          ],
          'brand' => $brand,
          "mattype" => [[
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
          ]],
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
        return Redirect::route('request');
    }
}
