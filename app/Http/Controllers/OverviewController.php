<?php

namespace App\Http\Controllers;

use App\Exports\OverviewExport;
use App\Services\OverviewActions;
use App\Services\OverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OverviewController extends Controller
{
    public function show(Request $request, OverviewService $service, string $kind)
    {
        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'FG_BOM' => ['sometimes', 'required', 'string', 'max:500'],
            'GTIN' => ['required_with:FG_BOM', 'string', 'max:500'],
            'FG_ID' => ['required_with:FG_BOM', 'string', 'max:500'],
        ]);
        try {
            $identity = $kind === 'products' && $request->has('FG_BOM') ? $request->only(['FG_BOM', 'GTIN', 'FG_ID']) : null;
            $data = $service->load($kind, (int) $request->input('page', 1), $identity);
            $data['actions'] = $kind === 'products' && $data['records'] ? app(OverviewActions::class)->options($data['records'][0]) : [1 => [['path' => [], 'label' => '', 'fields' => []]]];
            foreach ($data['actions'] as &$options) {
                $options = array_map(fn ($option) => ['path' => $option['path'], 'label' => $option['label']], $options);
            }

            return Inertia::render('Overview/Index', array_merge($data, ['kind' => $kind, 'error' => null]));
        } catch (\Throwable $exception) {
            report($exception);

            return Inertia::render('Overview/Index', [
                'kind' => $kind, 'title' => OverviewService::DEFINITIONS[$kind]['title'],
                'records' => [], 'page' => 0, 'total' => 0,
                'error' => 'ไม่สามารถอ่านข้อมูล Overview ได้ กรุณาลองใหม่หรือติดต่อผู้ดูแลระบบ',
            ])->toResponse($request)->setStatusCode(503);
        }
    }

    public function add(Request $request, OverviewActions $actions, int $action)
    {
        abort_unless(isset(OverviewActions::ROUTES[$action]), 404);
        $validated = $request->validate([
            'FG_BOM' => [$action === 1 ? 'nullable' : 'required', 'string', 'max:500'],
            'GTIN' => [$action === 1 ? 'nullable' : 'required', 'string', 'max:500'],
            'FG_ID' => [$action === 1 ? 'nullable' : 'required', 'string', 'max:500'],
            'path' => ['sometimes', 'array', 'max:4'], 'path.*' => ['required', 'string', 'max:500'],
        ]);
        $header = $request->only(['FG_BOM', 'GTIN', 'FG_ID']);
        $path = $validated['path'] ?? [];
        $fields = $action === 1 ? [] : $actions->resolve($header, $action, $path);
        $token = Crypt::encryptString(json_encode([
            'user' => (string) $request->user()->getAuthIdentifier(), 'expires' => time() + 14400,
            'header' => $header, 'action' => $action, 'path' => $path,
        ], JSON_THROW_ON_ERROR));

        return redirect()->route(OverviewActions::ROUTES[$action][0], array_merge($fields, ['overviewToken' => $token]));
    }

    public function export(OverviewService $service, string $kind)
    {
        try {
            $data = $service->load($kind, null);
            if (! $data['total']) {
                return redirect()->route('overview.'.$kind)->with('error', 'ไม่มีรายการ Overview สำหรับ Export');
            }

            // Build the complete workbook before returning a download. Never return partial data.
            return Excel::download(new OverviewExport($data['records']), 'overview-'.$kind.'.xlsx');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('overview.'.$kind)->with('error', 'Export ไม่สำเร็จ ไม่สามารถอ่านหรือสร้างไฟล์ข้อมูลครบชุดได้');
        }
    }
}
