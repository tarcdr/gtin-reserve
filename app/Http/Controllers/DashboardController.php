<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Dash;

class DashboardController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): Response
    {
      $message = '';
      $dash = Dash::first();
      if ($dash && $dash->message) {
        $message = $dash->message;
      }
      return Inertia::render('Dashboard', [
        "message" => $message,
      ]);
    }
}
