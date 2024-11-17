<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Dash;

class RmController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function viewComponentRequest(Request $request): Response
    {
      $boms = [
        ['code' => 'BOM001', 'label' => 'BOM 001 Description'],
        ['code' => 'BOM002', 'label' => 'BOM 002 Description'],
      ];
      return Inertia::render('RM/ComponentRequest', [ "boms" => $boms ]);
    }
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
      $input = [
        "bom" => $request->bom
      ];
      return Inertia::render('RM/MaterialCreate', [ "InputData" => $input ]);
    }

    public function report(Request $request): Response
    {
      $materials = [];
      return Inertia::render('RM/Report', [ "materials" => $materials ]);
    }
}
