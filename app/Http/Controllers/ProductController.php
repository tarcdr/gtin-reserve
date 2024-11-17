<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Dash;

class ProductController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
      return Inertia::render('NewProduct');
    }
}
