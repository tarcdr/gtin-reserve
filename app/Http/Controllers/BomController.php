<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Dash;

class BomController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
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
      return Inertia::render('Bom/New', [ "boms" => $boms, "subBoms" => $subBoms ]);
    }

    public function exists(Request $request, $id): Response
    {
      $input = [
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
      return Inertia::render('Bom/Exists', [ "InputData" => $input, "boms" => $boms, "subBoms" => $subBoms ]);
    }
}
