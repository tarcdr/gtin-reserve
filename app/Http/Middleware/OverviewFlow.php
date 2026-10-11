<?php

namespace App\Http\Middleware;

use App\Services\OverviewActions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class OverviewFlow
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('overviewToken');
        if (! $token) {
            return $next($request);
        }
        try {
            $context = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            abort(403, 'Invalid Overview context');
        }
        abort_unless(($context['user'] ?? null) === (string) $request->user()?->getAuthIdentifier()
            && ($context['expires'] ?? 0) >= time(), 403);
        $action = $context['action'];
        $routes = OverviewActions::ROUTES[$action] ?? [];
        $routeName = $request->route()->getName();
        abort_unless(in_array($routeName, $routes, true) || ($action === 1 && $routeName === 'product.view'), 403);
        $returnUrl = route('overview.products', $context['header'] ?? []);
        $fields = $action === 1 ? [] : app(OverviewActions::class)->resolve($context['header'], $action, $context['path']);
        $immutable = ['fgMaterialId', 'referentMaterialId', 'fgBomId', 'ownerLevel', 'semiFgLvBomId',
            'parentLevelBomId', 'semiFgLv2BomId', 'semi_fg_lv2_id', 'semi_fg_lv2_bom_id', 'rawMaterialId'];
        if (in_array($action, [4, 6, 7, 8], true)) {
            $immutable = array_merge($immutable, ['levelMaterialId', 'levelBomId']);
        }
        foreach (array_intersect_key($fields, array_flip($immutable)) as $key => $value) {
            if ($request->has($key) && $request->input($key) != $value) {
                throw ValidationException::withMessages(['overview' => 'Parent context ไม่ตรงกับรายการ Overview']);
            }
        }
        foreach (['fgDetail.semiFgLv2.id', 'fgDetail.semiFgLv2.bomId'] as $key) {
            $expected = data_get($fields, $key);
            if ($expected !== null && $request->has($key) && $request->input($key) != $expected) {
                throw ValidationException::withMessages(['overview' => 'Parent context ไม่ตรงกับรายการ Overview']);
            }
        }
        $request->merge($fields);
        $request->attributes->set('overviewFlow', ['token' => $token, 'returnUrl' => $returnUrl, 'fields' => $fields, 'action' => $action, 'header' => $context['header']]);
        if (! $request->isMethod('GET') && in_array($action, [7, 8], true)) {
            throw ValidationException::withMessages(['overview' => 'รอ flow บันทึก Raw Material Type4 จาก SA']);
        }
        $response = $next($request);
        if ($request->isMethod('PATCH') && in_array($routeName, $routes, true)
            && $response->isRedirection()) {
            if ($action === 1) {
                return redirect()->to($response->getTargetUrl().(str_contains($response->getTargetUrl(), '?') ? '&' : '?').'overviewToken='.urlencode($token));
            }

            return redirect()->to($returnUrl)->with('message', 'บันทึกสำเร็จ ข้อมูล Overview จะเปลี่ยนเมื่อกระบวนการเติม INDEX ทำงาน');
        }

        return $response;
    }
}
