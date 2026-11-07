<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use Closure;
use Illuminate\Http\Request;

class EnsureExportToSapAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isAdmin() || app(AppSettingsService::class)->exportTabEnabled()) {
            return $next($request);
        }

        $message = 'ผู้ดูแลระบบปิดการใช้งาน Export-to-SAP แล้ว';
        if ($request->isMethod('GET') && $request->routeIs('rm.report')) {
            return redirect()->route('dashboard')->with('exportAccessNotice', $message);
        }

        return response()->json(['message' => $message, 'code' => 'EXPORT_TAB_DISABLED'], 403);
    }
}
