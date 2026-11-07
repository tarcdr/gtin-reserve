<?php

namespace Tests\Feature;

use App\Http\Controllers\RmController;
use App\Http\Middleware\EnsureExportToSapAccess;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExportToSapTabTest extends TestCase
{
    private function install(): void
    {
        (require database_path('migrations/2026_11_02_000001_create_app_settings_table.php'))->up();
    }

    private function user(?string $role): void
    {
        $this->actingAs(new User(['user_login' => 'tester', 'role' => $role, 'is_active' => true]));
    }

    public function test_migration_is_repeatable_and_preserves_disabled_setting(): void
    {
        Schema::dropIfExists(AppSettingsService::TABLE);
        $this->assertTrue(app(AppSettingsService::class)->exportTabEnabled());
        $this->install();
        $this->assertTrue(app(AppSettingsService::class)->exportTabEnabled());
        app(AppSettingsService::class)->setExportTabEnabled(false, 'admin');
        $this->install();
        $this->assertFalse(app(AppSettingsService::class)->exportTabEnabled());
        $this->assertSame(1, DB::table(AppSettingsService::TABLE)->count());
    }

    public function test_admin_can_toggle_permanently_and_audit_is_recorded(): void
    {
        $this->install();
        $this->user('admin');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertRedirect(route('admin.users.index'));
        $this->assertFalse((new AppSettingsService)->exportTabEnabled());
        $row = DB::table(AppSettingsService::TABLE)->first();
        $this->assertSame('tester', $row->UPDATED_BY);
        $this->assertNotNull($row->UPDATED_AT);
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => true])->assertRedirect();
        $this->assertTrue((new AppSettingsService)->exportTabEnabled());
    }

    public function test_non_admin_is_forbidden_and_invalid_values_do_not_change_setting(): void
    {
        $this->install();
        $this->user('rd');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertForbidden();
        $this->user('admin');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => 'INVALID'])->assertSessionHasErrors('enabled');
        $this->assertTrue(app(AppSettingsService::class)->exportTabEnabled());
    }

    public function test_disabled_setting_is_shared_and_guards_report_without_blocking_admin(): void
    {
        $this->install();
        app(AppSettingsService::class)->setExportTabEnabled(false, 'admin');
        $this->user('rd');
        Route::middleware(['web', 'auth'])->get('/_test/feature-state', fn () => Inertia::render('Dashboard', []));
        $this->get('/_test/feature-state')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->where('features.exportToSapTabEnabled', false));
        // Stub only the report body: middleware and the real route remain in place.
        $this->app->instance(RmController::class, new class extends RmController
        {
            public function __construct() {}

            public function report(Request $request, $tab = 'AVAILABILITY'): Response
            {
                return Inertia::render('RM/Report', []);
            }
        });
        $this->get(route('rm.report'))->assertRedirect(route('dashboard'))->assertSessionHas('exportAccessNotice');
        $this->user('ADMIN');
        $this->get(route('rm.report'))->assertOk();
    }

    public function test_missing_migration_is_reported_on_update(): void
    {
        Schema::dropIfExists(AppSettingsService::TABLE);
        $this->user('admin');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertSessionHasErrors('enabled');
    }

    public function test_admin_role_variants_share_permission_and_can_toggle(): void
    {
        $this->install();
        Route::middleware(['web', 'auth'])->get('/_test/admin-permission', fn () => Inertia::render('Dashboard', []));
        foreach (['ADMIN', 'admin', ' Admin ', "\tADMIN\n"] as $role) {
            $this->user($role);
            $this->assertTrue(auth()->user()->isAdmin());
            $this->get('/_test/admin-permission')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->where('auth.canManageExportTab', true));
            foreach ([false, true] as $enabled) {
                $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => $enabled])->assertRedirect(route('admin.users.index'));
                $this->assertSame($enabled, app(AppSettingsService::class)->exportTabEnabled());
            }
        }
    }

    public function test_other_and_missing_roles_cannot_toggle_or_manage_accounts(): void
    {
        $this->install();
        Route::middleware(['web', 'auth'])->get('/_test/admin-permission', fn () => Inertia::render('Dashboard', []));
        foreach (['RD', 'PUR', 'GTIN', '', null, 'administrator', '   '] as $role) {
            $this->user($role);
            $this->assertFalse(auth()->user()->isAdmin());
            $this->get(route('admin.users.index'))->assertForbidden();
            $this->get('/_test/admin-permission')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.canManageExportTab', false));
            $before = (array) DB::table(AppSettingsService::TABLE)->first();
            $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertForbidden();
            $this->assertSame($before, (array) DB::table(AppSettingsService::TABLE)->first());
        }
    }

    public function test_guest_cannot_toggle_export_setting(): void
    {
        $this->install();
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertRedirect(route('login'));
        $this->assertTrue(app(AppSettingsService::class)->exportTabEnabled());
    }

    public function test_access_endpoint_reads_current_setting_and_admin_bypasses_closure(): void
    {
        $this->install();
        $this->get(route('export-to-sap.access'))->assertRedirect(route('login'));
        foreach ([true, false, true] as $enabled) {
            app(AppSettingsService::class)->setExportTabEnabled($enabled, 'admin');
            foreach (['ADMIN', 'admin', ' Admin ', 'RD', 'PUR', 'GTIN', '', null] as $role) {
                $this->user($role);
                $response = $this->getJson(route('export-to-sap.access'))->assertOk()->assertExactJson([
                    'enabled' => $enabled, 'canAccess' => $enabled || auth()->user()->isAdmin(),
                ]);
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            }
        }
    }

    public function test_closed_tab_blocks_every_report_action_before_controller_and_reopens(): void
    {
        $this->install();
        $controller = new class extends RmController
        {
            public int $calls = 0;

            public function report(Request $request, $tab = 'AVAILABILITY'): Response
            {
                $this->calls++;

                return Inertia::render('RM/Report', []);
            }

            public function export(Request $request)
            {
                $this->calls++;

                return response()->json(['download' => true]);
            }

            public function exportToSap(Request $request)
            {
                $this->calls++;

                return response()->json(['sap' => true]);
            }

            public function update(Request $request): RedirectResponse
            {
                $this->calls++;

                return redirect()->route('dashboard');
            }

            public function delete(Request $request): RedirectResponse
            {
                $this->calls++;

                return redirect()->route('dashboard');
            }
        };
        $this->app->instance(RmController::class, $controller);
        app(AppSettingsService::class)->setExportTabEnabled(false, 'admin');
        foreach (['RD', 'PUR', 'GTIN', '', null] as $role) {
            $this->user($role);
            $this->get(route('rm.report', ['tab' => 'GENERAL']))->assertRedirect(route('dashboard'));
            $this->get(route('rm.export'))->assertForbidden()->assertJsonPath('code', 'EXPORT_TAB_DISABLED');
            $this->patchJson(route('rm.confirm'))->assertForbidden();
            $this->deleteJson(route('rm.delete'))->assertForbidden();
            $this->postJson(route('rm.export-sap'))->assertForbidden();
        }
        $this->assertSame(0, $controller->calls);
        foreach (['ADMIN', 'admin', ' Admin '] as $role) {
            $this->user($role);
            $this->get(route('rm.report'))->assertOk();
            $this->get(route('rm.export'))->assertOk();
            $this->patchJson(route('rm.confirm'))->assertRedirect();
            $this->deleteJson(route('rm.delete'))->assertRedirect();
            $this->postJson(route('rm.export-sap'))->assertOk();
        }
        $this->assertSame(15, $controller->calls);
        app(AppSettingsService::class)->setExportTabEnabled(true, 'admin');
        $this->user('RD');
        $this->get(route('rm.report'))->assertOk();
        $this->get(route('rm.export'))->assertOk();
        $this->patchJson(route('rm.confirm'))->assertRedirect();
        $this->deleteJson(route('rm.delete'))->assertRedirect();
        $this->postJson(route('rm.export-sap'))->assertForbidden();
        $this->assertSame(19, $controller->calls);
        foreach (['rm.material_create', 'rm.component_request'] as $name) {
            $this->assertNotContains(EnsureExportToSapAccess::class, app('router')->getRoutes()->getByName($name)->gatherMiddleware());
        }
    }
}
