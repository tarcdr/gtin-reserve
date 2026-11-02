<?php

namespace App\Http\Controllers;

use App\Services\AppSettingsService;
use Illuminate\Http\Request;

class ExportToSapTabController extends Controller
{
    public function __invoke(Request $request, AppSettingsService $settings)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $settings->setExportTabEnabled((bool) $data['enabled'], (string) $request->user()->user_login);
        return redirect()->route('admin.users.index')->with('message', 'บันทึกการแสดงเมนู Export-to-SAP แล้ว');
    }
}
