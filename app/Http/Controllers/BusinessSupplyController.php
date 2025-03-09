<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSupplyController extends Controller
{
  public function create(Request $request): Response
  {
    $InputData = [
      'bom' => 'BOMXXXX'
    ];
    return Inertia::render('BusinessSupply/Create', compact('InputData'));
  }
}
