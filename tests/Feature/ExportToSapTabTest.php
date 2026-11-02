<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExportToSapTabTest extends TestCase
{
    private function install(): void { (require database_path('migrations/2026_11_02_000001_create_app_settings_table.php'))->up(); }
    private function user(string $role): void { $this->actingAs(new User(['user_login' => 'tester', 'role' => $role, 'is_active' => true])); }

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
        $this->install(); $this->user('admin');
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
        $this->install(); $this->user('rd');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertForbidden();
        $this->user('admin');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => 'INVALID'])->assertSessionHasErrors('enabled');
        $this->assertTrue(app(AppSettingsService::class)->exportTabEnabled());
    }
    public function test_disabled_setting_is_shared_and_does_not_guard_existing_export_route(): void
    {
        $this->install(); app(AppSettingsService::class)->setExportTabEnabled(false, 'admin'); $this->user('rd');
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth'])->get('/_test/feature-state', fn () => \Inertia\Inertia::render('Dashboard', []));
        $this->get('/_test/feature-state')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->where('features.exportToSapTabEnabled', false));
        // Stub only the report body: middleware and the real route remain in place.
        $this->app->instance(\App\Http\Controllers\RmController::class, new class extends \App\Http\Controllers\RmController {
            public function __construct() {}
            public function report(\Illuminate\Http\Request $request, $tab = 'AVAILABILITY'): \Inertia\Response { return \Inertia\Inertia::render('RM/Report', []); }
        });
        $this->get(route('rm.report'))->assertOk();
    }
    public function test_missing_migration_is_reported_on_update(): void
    {
        Schema::dropIfExists(AppSettingsService::TABLE); $this->user('admin');
        $this->patch(route('admin.settings.export-to-sap-tab'), ['enabled' => false])->assertSessionHasErrors('enabled');
    }
}
