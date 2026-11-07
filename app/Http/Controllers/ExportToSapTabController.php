<?php

namespace App\Http\Controllers;

use App\Services\AppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExportToSapTabController extends Controller
{
    public function access(Request $request, AppSettingsService $settings)
    {
        $enabled = $settings->exportTabEnabled();

        return response()->json([
            'enabled' => $enabled,
            'canAccess' => $enabled || $request->user()->isAdmin(),
        ])->header('Cache-Control', 'no-store');
    }

    public function audit(Request $request, AppSettingsService $settings)
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        try {
            return response()->json($settings->history((int) $request->input('page', 1)))->header('Cache-Control', 'no-store');
        } catch (ValidationException $exception) {
            return response()->json(['message' => $exception->errors()['enabled'][0]], 503);
        }
    }

    public function __invoke(Request $request, AppSettingsService $settings)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $settings->setExportTabEnabled((bool) $data['enabled'], (string) $request->user()->user_login);

        return redirect()->route('admin.users.index')->with('message', 'บันทึกการแสดงเมนู Export-to-SAP แล้ว');
    }
}
