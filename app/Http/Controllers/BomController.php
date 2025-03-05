<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BomController extends Controller
{
  public function create(Request $request): Response
  {
    $InputData = [
      'bom' => 'BOMXXXX'
    ];
    return Inertia::render('Bom/Create', compact('InputData'));
  }

  public function view(Request $request): Response
  {
    $InputData = $request->InputData;
    $boms = [
      ['code' => 'BOM001', 'label' => 'BOM 001 Description'],
      ['code' => 'BOM002', 'label' => 'BOM 002 Description'],
    ];
    $subBoms = [
      'BOM001' => [
        ['code' => 'MAT1', 'label' => 'Material 1 Item 1'],
        ['code' => 'MAT2', 'label' => 'Material 1 Item 2'],
      ],
      'BOM002' => [
        ['code' => 'MAT2', 'label' => 'Material 2 Item 1'],
        ['code' => 'MAT3', 'label' => 'Material 2 Item 2'],
      ],
    ];
    return Inertia::render('Bom/New', compact('InputData', 'boms',  'subBoms'));
  }

  public function exists(Request $request, $id): Response
  {
    $InputData = [
      "bom" => $id
    ];
    $boms = [
      ['code' => 'BOM001', 'label' => 'BOM 001 Description'],
      ['code' => 'BOM002', 'label' => 'BOM 002 Description'],
    ];
    $subBoms = [
      'BOM001' => [
        ['code' => 'MAT1', 'label' => 'Material 1 Item 1'],
        ['code' => 'MAT2', 'label' => 'Material 1 Item 2'],
      ],
      'BOM002' => [
        ['code' => 'MAT2', 'label' => 'Material 2 Item 1'],
        ['code' => 'MAT3', 'label' => 'Material 2 Item 2'],
      ],
    ];
    return Inertia::render('Bom/Exists', compact('InputData', 'boms', 'subBoms'));
  }

  public function process(Request $request): RedirectResponse
  {
    $InputData = [
      'bom'         => $request->bom,
      'description' => $request->description,
    ];
    return redirect()
        ->route('bom.new', compact('InputData'))
        ->with('success', 'Bom created.');
  }
}
