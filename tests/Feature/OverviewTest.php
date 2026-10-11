<?php

namespace Tests\Feature;

use App\Exports\OverviewExport;
use App\Models\User;
use App\Services\OverviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('oracle');
        $schema = DB::connection('oracle')->getSchemaBuilder();
        foreach (OverviewService::DEFINITIONS as $definition) {
            $header = str_replace('_V', '', $definition['header']);
            $schema->create($header, function (Blueprint $table) use ($definition) {
                foreach (array_merge($definition['keys'], ['ETS_STATUS'], isset($definition['sections'][0]) && $definition['sections'][0][4] === 'BS_DT' ? ['BS_TYPE'] : []) as $key) {
                    $table->string($key)->nullable();
                }
            });
            foreach ($definition['sections'] as [$label, $id, $component, $status, $suffix, $parents]) {
                $actionSuffix = match ($suffix) {
                    'SFGL2' => 'SEMI_FG_LV_2', 'SFGL1' => 'SEMI_FG_LV_1',
                    'MAT4' => 'RAW_MAT4', 'BS_DT' => 'BS', default => 'FG',
                };
                $columns = array_unique(array_merge($definition['keys'], $parents, [$id, $component, $status, 'ACTION_DETAILS', 'BUILT_ACTION_'.$actionSuffix, 'COMPLETE_ACTION_'.$actionSuffix]));
                $schema->create('PROJ1_2_INDEX_SECTION_'.$suffix, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $column) {
                        $table->string($column)->nullable();
                    }
                });
            }
        }
        foreach ([
            'PROJ1_2_DML_FG_MATTYPE_1' => ['FG_BOM_ID', 'MATERIAL_ID_FG_1', 'DESC_FG_BOM_ID', 'SITE', 'STATUS_ROW', 'STATUS'],
            'PROJ1_2_DML_SEMI_L2_ID' => ['FG_BOM_ID', 'MATERIAL_ID_FG_1', 'SEMI_FG_LV2_ID', 'SEMI_FG_LV2_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_ID' => ['FG_BOM_ID', 'MATERIAL_ID_FG_1', 'SEMI_FG_LV2_ID', 'SEMI_FG_LV2_BOM_ID', 'SEMI_FG_LV1_ID', 'SEMI_FG_LV1_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_COMP_M4' => ['SEMI_FG_LV1_BOM_ID', 'MATERIAL_ID_M4', 'STATUS_ROW'],
        ] as $name => $columns) {
            $schema->create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        // Exercise the shipped SELECT projections and ETS filters using SQLite-compatible view DDL.
        $sql = file_get_contents(base_path('database/sql/overview_views.sql'));
        $sql = preg_replace('/^--.*$|^WHENEVER.*$/m', '', $sql);
        $sql = str_replace('CREATE OR REPLACE VIEW', 'CREATE VIEW', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            DB::connection('oracle')->statement($statement);
        }
        $this->withoutVite();
    }

    private function insert(string $table, array $rows): void
    {
        DB::connection('oracle')->table('PROJ1_2_INDEX_SECTION_'.preg_replace('/_V$/', '', $table))->insert($rows);
    }

    private function seedProducts(): void
    {
        $this->insert('HEADER', [
            ['FG_BOM' => 'B2', 'GTIN' => '000002', 'FG_ID' => '0010', 'ETS_STATUS' => null],
            ['FG_BOM' => 'B1', 'GTIN' => '000001', 'FG_ID' => '0010', 'ETS_STATUS' => null],
            ['FG_BOM' => 'B3', 'GTIN' => '000003', 'FG_ID' => '0010', 'ETS_STATUS' => 'Y'],
        ]);
        $this->insert('FG_V', [
            ['FG_BOM' => 'B1', 'GTIN' => '000001', 'FG_ID' => '0010', 'COMPONENTS_FG' => '0005', 'STATUS_FG' => 'NEW', 'ACTION_DETAILS' => '=1+1', 'BUILT_ACTION_FG' => 'Y', 'COMPLETE_ACTION_FG' => 'N'],
            ['FG_BOM' => 'B2', 'GTIN' => '000002', 'FG_ID' => '0010', 'COMPONENTS_FG' => '0006', 'STATUS_FG' => 'EXISTING', 'ACTION_DETAILS' => null, 'BUILT_ACTION_FG' => null, 'COMPLETE_ACTION_FG' => null],
            ['FG_BOM' => 'B1', 'GTIN' => '000009', 'FG_ID' => '0010', 'COMPONENTS_FG' => 'WRONG', 'STATUS_FG' => 'NEW', 'ACTION_DETAILS' => null, 'BUILT_ACTION_FG' => null, 'COMPLETE_ACTION_FG' => null],
        ]);
    }

    private function login(): void
    {
        $this->actingAs(new User(['user_login' => 'overview-test', 'employee_name' => 'Tester', 'role' => 'user']));
    }

    public function test_overview_routes_require_authentication(): void
    {
        foreach (['products', 'bs'] as $kind) {
            $this->get('/overview/'.$kind)->assertRedirect('/login');
            $this->get('/overview/'.$kind.'/export')->assertRedirect('/login');
        }
    }

    public function test_product_pages_filter_headers_and_keep_bom_gtin_details_separate(): void
    {
        $this->seedProducts();
        $this->login();
        $this->get('/overview/products')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Overview/Index')->where('total', 2)->where('page', 1)
            ->where('records.0.header.0.value', 'B1')
            ->has('records.0.sections', 4)->has('records.0.sections.0.rows', 1)
            ->where('records.0.sections.0.rows.0.component', '0005')
            ->where('records.0.sections.0.rows.0.built', '1')
            ->where('records.0.sections.0.rows.0.complete', '0')->where('error', null));
        $this->get('/overview/products?page=999')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('page', 2)->where('records.0.header.0.value', 'B2')
            ->where('records.0.sections.0.rows.0.component', '0006')
            ->where('records.0.sections.0.rows.0.built', '—'));
        $this->get('/overview/products?page=0')->assertSessionHasErrors('page');
        $this->get('/overview/products?page=abc')->assertSessionHasErrors('page');
    }

    public function test_parent_paths_duplicate_details_and_duplicate_headers_are_preserved(): void
    {
        $this->seedProducts();
        $header = ['FG_BOM' => 'B1', 'GTIN' => '000001', 'FG_ID' => '0010'];
        $this->insert('HEADER', [$header]);
        $row = array_merge($header, ['SEMI_FG_LV_2_ID' => '0020', 'SEMI_FG_LV_1_ID' => '0030', 'RAW_MAT_TYPE4_ID' => '0040', 'COMPONENTS_RAW_MAT4' => '0041']);
        $this->insert('MAT4_V', [$row, $row, array_merge($row, ['SEMI_FG_LV_2_ID' => '0021'])]);
        $data = app(OverviewService::class)->load('products', null);
        $this->assertSame(3, $data['total']);
        $rows = $data['records'][0]['sections'][3]['rows'];
        $this->assertCount(3, $rows);
        $this->assertSame(['0020', '0030'], $rows[0]['parents']);
        $this->assertSame(['0021', '0030'], $rows[2]['parents']);
        $this->assertSame($data['records'][0], $data['records'][1]);
        $this->assertSame([], $data['records'][0]['sections'][1]['rows']);
    }

    public function test_semi_fg_sections_keep_parent_paths_and_flags(): void
    {
        $this->seedProducts();
        $header = ['FG_BOM' => 'B1', 'GTIN' => '000001', 'FG_ID' => '0010'];
        $this->insert('SFGL2_V', [array_merge($header, [
            'SEMI_FG_LV_2_ID' => '0020', 'COMPONENTS_SEMI_FG_LV_2' => '0030',
            'STATUS_SEMI_FG_LV_2' => 'NEW', 'BUILT_ACTION_SEMI_FG_LV_2' => 'Y',
            'COMPLETE_ACTION_SEMI_FG_LV_2' => 'Y',
        ])]);
        $row = array_merge($header, [
            'SEMI_FG_LV_2_ID' => '0020', 'SEMI_FG_LV_1_ID' => '0030',
            'COMPONENTS_SEMI_FG_LV_1' => '0040', 'BUILT_ACTION_SEMI_FG_LV_1' => 'N',
            'COMPLETE_ACTION_SEMI_FG_LV_1' => null,
        ]);
        $this->insert('SFGL1_V', [$row, array_merge($row, ['SEMI_FG_LV_2_ID' => '0021'])]);
        $sections = app(OverviewService::class)->load('products')['records'][0]['sections'];
        $this->assertSame('1', $sections[1]['rows'][0]['complete']);
        $this->assertCount(2, $sections[2]['rows']);
        $this->assertSame(['0020'], $sections[2]['rows'][0]['parents']);
        $this->assertSame(['0021'], $sections[2]['rows'][1]['parents']);
        $this->assertSame('0', $sections[2]['rows'][0]['built']);
        $this->assertSame('—', $sections[2]['rows'][0]['complete']);
        $this->assertSame('unexpected', OverviewService::flag('unexpected'));
    }

    public function test_bs_keys_and_ets_filter_are_applied(): void
    {
        $this->insert('BS_HT', [
            ['BOM_BUSINESS_SUPPLY' => 'B1', 'BS_GTIN' => '001', 'BS_ID' => '0060', 'BS_TYPE' => 'Under Type FG', 'ETS_STATUS' => null],
            ['BOM_BUSINESS_SUPPLY' => 'B2', 'BS_GTIN' => '002', 'BS_ID' => '0060', 'BS_TYPE' => 'Other', 'ETS_STATUS' => 'Y'],
        ]);
        $this->insert('BS_DT_V', [
            ['BOM_BUSINESS_SUPPLY' => 'B1', 'BS_GTIN' => '001', 'BS_ID' => '0060', 'COMPONENTS_BS' => '0050'],
            ['BOM_BUSINESS_SUPPLY' => 'B2', 'BS_GTIN' => '002', 'BS_ID' => '0060', 'COMPONENTS_BS' => 'WRONG'],
        ]);
        $this->login();
        $this->get('/overview/bs')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('total', 1)->where('records.0.header.3.value', 'Under Type FG')
            ->has('records.0.sections.0.rows', 1)->where('records.0.sections.0.rows.0.component', '0050'));
    }

    public function test_empty_data_is_not_a_database_error_and_cannot_export(): void
    {
        $this->login();
        $this->get('/overview/products')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('total', 0)->where('page', 0)->where('records', [])->where('error', null));
        $this->get('/overview/products/export')->assertRedirect('/overview/products')->assertSessionHas('error');
    }

    public function test_missing_detail_view_returns_error_and_never_partial_download(): void
    {
        $this->seedProducts();
        DB::connection('oracle')->statement('DROP VIEW PROJ1_2_INDEX_SECTION_MAT4_V');
        $this->login();
        $this->get('/overview/products')->assertStatus(503)->assertInertia(fn (Assert $page) => $page
            ->where('records', [])->where('error', fn ($error) => is_string($error) && $error !== ''));
        $this->get('/overview/products/export')->assertRedirect('/overview/products')->assertSessionHas('error');
    }

    public function test_excel_download_contains_all_headers_and_explicit_text_cells(): void
    {
        $this->seedProducts();
        $this->login();
        $response = $this->get('/overview/products/export?page=1');
        $response->assertDownload('overview-products.xlsx');
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(2, $workbook->getSheetCount());
        $this->assertSame('B1', $workbook->getSheet(0)->getCell('B1')->getValue());
        $this->assertSame('B2', $workbook->getSheet(1)->getCell('B1')->getValue());
        $this->assertSame('000001', $workbook->getSheet(0)->getCell('B2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $workbook->getSheet(0)->getCell('B2')->getDataType());
        $cells = $workbook->getSheet(0)->toArray();
        $this->assertSame('0005', $cells[6][1]);
        $this->assertSame('=1+1', $cells[6][3]);
        $this->assertSame(DataType::TYPE_STRING, $workbook->getSheet(0)->getCell('D7')->getDataType());
        $this->assertSame('1', $cells[6][4]);
        $this->assertSame('0', $cells[6][5]);
        $workbook->disconnectWorksheets();
    }

    public function test_bs_workbook_exports_all_headers_and_retains_null_flags(): void
    {
        $this->insert('BS_HT', [
            ['BOM_BUSINESS_SUPPLY' => 'B1', 'BS_GTIN' => '001', 'BS_ID' => '0060', 'BS_TYPE' => 'Type1'],
            ['BOM_BUSINESS_SUPPLY' => 'B2', 'BS_GTIN' => '002', 'BS_ID' => '0060', 'BS_TYPE' => 'Type2'],
        ]);
        $this->insert('BS_DT_V', [[
            'BOM_BUSINESS_SUPPLY' => 'B1', 'BS_GTIN' => '001', 'BS_ID' => '0060',
            'COMPONENTS_BS' => '0050', 'BUILT_ACTION_BS' => null, 'COMPLETE_ACTION_BS' => 'Y',
        ]]);
        $data = app(OverviewService::class)->load('bs', null);
        $bytes = Excel::raw(new OverviewExport($data['records']), \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'overview-test-');
        try {
            file_put_contents($file, $bytes);
            $book = IOFactory::load($file);
            $this->assertSame(2, $book->getSheetCount());
            $this->assertSame('Type2', $book->getSheet(1)->getCell('B4')->getValue());
            $this->assertSame('—', $book->getSheet(0)->getCell('E8')->getValue());
            $this->assertSame('1', $book->getSheet(0)->getCell('F8')->getValue());
            $this->assertSame('ไม่มีข้อมูลในส่วนนี้', $book->getSheet(1)->getCell('A8')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    private function seedActionParents(): array
    {
        $this->seedProducts();
        $header = ['FG_BOM' => 'B1', 'GTIN' => '000001', 'FG_ID' => '0010'];
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->insert(['FG_BOM_ID' => 'B1', 'MATERIAL_ID_FG_1' => '0010', 'STATUS_ROW' => 'INS']);
        foreach (['0020', '0021'] as $id) {
            DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L2_ID')->insert(['FG_BOM_ID' => 'B1', 'MATERIAL_ID_FG_1' => '0010', 'SEMI_FG_LV2_ID' => $id, 'SEMI_FG_LV2_BOM_ID' => 'B'.$id, 'STATUS_ROW' => 'INS']);
            DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_ID')->insert(['FG_BOM_ID' => 'B1', 'MATERIAL_ID_FG_1' => '0010', 'SEMI_FG_LV2_ID' => $id, 'SEMI_FG_LV2_BOM_ID' => 'B'.$id, 'SEMI_FG_LV1_ID' => '0030', 'SEMI_FG_LV1_BOM_ID' => 'B'.$id.'0030', 'STATUS_ROW' => 'INS']);
            DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->insert(['SEMI_FG_LV1_BOM_ID' => 'B'.$id.'0030', 'MATERIAL_ID_M4' => 'TYPE4', 'STATUS_ROW' => 'INS']);
            $this->insert('SFGL2_V', [array_merge($header, ['SEMI_FG_LV_2_ID' => $id, 'COMPONENTS_SEMI_FG_LV_2' => '0030'])]);
            $this->insert('SFGL1_V', [array_merge($header, ['SEMI_FG_LV_2_ID' => $id, 'SEMI_FG_LV_1_ID' => '0030', 'COMPONENTS_SEMI_FG_LV_1' => 'TYPE4'])]);
        }

        return $header;
    }

    public function test_actions_resolve_multiple_paths_typed_materials_and_completed_owners(): void
    {
        $header = $this->seedActionParents();
        $service = app(\App\Services\OverviewActions::class);
        $record = app(OverviewService::class)->load('products')['records'][0];
        $options = $service->options($record);
        foreach ([1 => 1, 2 => 1, 3 => 1, 4 => 2, 5 => 2, 6 => 2, 7 => 2, 8 => 2] as $action => $count) {
            $this->assertCount($count, $options[$action]);
        }
        $fields = $service->resolve($header, 5, ['0010', '0021']);
        $this->assertSame('B0021', $fields['parentLevelBomId']);
        $this->assertSame('0021', $fields['semi_fg_lv2_id']);
        $this->assertSame('TYPE4', $options[8][0]['fields']['rawMaterialId']);
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->delete();
        $options = $service->options($record);
        $this->assertCount(2, $options[7]);
        $this->assertCount(0, $options[8]);
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L2_ID')->where('SEMI_FG_LV2_ID', '0020')->update(['STATUS_ROW' => 'COM']);
        $options = $service->options($record);
        $this->assertCount(1, $options[4]);
        $this->assertCount(1, $options[6]);
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->update(['STATUS_ROW' => 'COM']);
        $options = $service->options($record);
        foreach (range(2, 8) as $action) {
            $this->assertSame([], $options[$action]);
        }
        $this->assertCount(1, $options[1]);
    }

    public function test_add_endpoint_rejects_foreign_paths_and_signs_context(): void
    {
        $header = $this->seedActionParents();
        $this->get('/overview/products/add/5')->assertRedirect('/login');
        $this->login();
        $this->get('/overview/products/add/5?'.http_build_query(array_merge($header, ['path' => ['0010', 'WRONG']])))->assertSessionHasErrors('overview');
        $response = $this->get('/overview/products/add/5?'.http_build_query(array_merge($header, ['path' => ['0010', '0021']])))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $context = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($query['overviewToken']), true);
        $this->assertSame($header, $context['header']);
        $this->assertSame(['0010', '0021'], $context['path']);
        $this->assertSame('B0021', $query['parentLevelBomId']);
        $this->get('/overview/products/add/5?'.http_build_query(array_merge($header, ['GTIN' => 'WRONG', 'path' => ['0010', '0021']])))->assertSessionHasErrors('overview');
        $this->get('/overview/products/add/1?'.http_build_query($header))->assertRedirect();
    }

    public function test_return_identity_finds_original_header_and_falls_back_if_removed(): void
    {
        $this->seedProducts();
        $this->login();
        $header = ['FG_BOM' => 'B2', 'GTIN' => '000002', 'FG_ID' => '0010'];
        $this->get('/overview/products?'.http_build_query($header))->assertInertia(fn (Assert $page) => $page->where('page', 2)->where('notice', null));
        DB::connection('oracle')->table('PROJ1_2_INDEX_SECTION_HEADER')->where('FG_BOM', 'B2')->update(['ETS_STATUS' => 'Y']);
        $this->get('/overview/products?'.http_build_query($header))->assertInertia(fn (Assert $page) => $page->where('page', 1)->where('records.0.identity.FG_BOM', 'B1')->where('notice', fn ($notice) => is_string($notice)));
    }

    public function test_signed_flow_revalidates_parent_and_blocks_raw_save(): void
    {
        $header = $this->seedActionParents();
        $this->login();
        // Exercise middleware without invoking Oracle write procedures or unrelated form master queries.
        $request = \Illuminate\Http\Request::create('/test', 'PATCH', []);
        $request->setUserResolver(fn () => auth()->user());
        $request->setLaravelSession(app('session')->driver());
        $route = new \Illuminate\Routing\Route('PATCH', '/test', fn () => null);
        $route->name('packmaterial.semi-fg-lv1-bom.save');
        $request->setRouteResolver(fn () => $route);
        $context = ['user' => (string) auth()->id(), 'expires' => time() + 600, 'header' => $header, 'action' => 6, 'path' => ['0010', '0021', '0030']];
        $token = \Illuminate\Support\Facades\Crypt::encryptString(json_encode($context));
        $request->merge(['overviewToken' => $token]);
        $middleware = new \App\Http\Middleware\OverviewFlow;
        $request->session()->put('errors', new \Illuminate\Support\ViewErrorBag);
        $response = $middleware->handle($request, function ($request) {
            $this->assertSame('B00210030', $request->input('semiFgLvBomId'));

            return redirect('/old-form');
        });
        $this->assertSame(route('overview.products', $header), $response->getTargetUrl());
        $request->merge(['fgBomId' => 'FOREIGN']);
        try {
            $middleware->handle($request, fn () => $this->fail('Wrong parent accepted'));
            $this->fail('Validation missing');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('overview', $exception->errors());
        }
        $request->replace(['overviewToken' => $token]);
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->update(['STATUS_ROW' => 'COM']);
        try {
            $middleware->handle($request, fn () => $this->fail('Completed owner accepted'));
            $this->fail('Validation missing');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('overview', $exception->errors());
        }
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->update(['STATUS_ROW' => 'INS']);
        $route = new \Illuminate\Routing\Route('PATCH', '/test', fn () => null);
        $route->name('material-levels.raw.save');
        $request->setRouteResolver(fn () => $route);
        $context['action'] = 7;
        $request->replace(['overviewToken' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($context))]);
        try {
            $middleware->handle($request, fn () => $this->fail('Raw save accepted'));
            $this->fail('Validation missing');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertStringContainsString('SA', $exception->errors()['overview'][0]);
        }
        $context['expires'] = time() - 1;
        $request->replace(['overviewToken' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($context))]);
        try {
            $middleware->handle($request, fn () => $this->fail('Expired token accepted'));
            $this->fail('403 missing');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $context['expires'] = time() + 600;
        $context['user'] = 'someone-else';
        $request->replace(['overviewToken' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($context))]);
        try {
            $middleware->handle($request, fn () => $this->fail('Foreign token accepted'));
            $this->fail('403 missing');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_export_merges_only_contiguous_matching_parent_paths(): void
    {
        $header = $this->seedActionParents();
        $row = array_merge($header, ['SEMI_FG_LV_2_ID' => '0020', 'SEMI_FG_LV_1_ID' => '0030', 'COMPONENTS_SEMI_FG_LV_1' => 'TYPE4']);
        $this->insert('SFGL1_V', [$row]);
        $this->login();
        $response = $this->get('/overview/products/export');
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $merges = $book->getSheet(0)->getMergeCells();
        $this->assertContains('A16:A17', $merges);
        $this->assertContains('B16:B17', $merges);
        $this->assertSame('0021', $book->getSheet(0)->getCell('A18')->getValue());
        $this->assertSame('TYPE4', $book->getSheet(0)->getCell('C17')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_new_product_keeps_detail_redirect_and_only_uses_header_for_return(): void
    {
        $this->login();
        $header = ['FG_BOM' => 'ORIGINAL', 'GTIN' => '000001', 'FG_ID' => 'OLD'];
        $response = $this->get('/overview/products/add/1?'.http_build_query($header));
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame(['overviewToken'], array_keys($query));
        $request = \Illuminate\Http\Request::create('/test', 'PATCH', ['overviewToken' => $query['overviewToken'], 'bomId' => 'NEW', 'materialId' => 'NEWID']);
        $request->setUserResolver(fn () => auth()->user());
        $request->setLaravelSession(app('session')->driver());
        $route = new \Illuminate\Routing\Route('PATCH', '/test', fn () => null);
        $route->name('product.create');
        $request->setRouteResolver(fn () => $route);
        $response = (new \App\Http\Middleware\OverviewFlow)->handle($request, function ($request) {
            $this->assertSame('NEW', $request->input('bomId'));
            $this->assertSame('NEWID', $request->input('materialId'));
            $this->assertSame(route('overview.products', ['FG_BOM' => 'ORIGINAL', 'GTIN' => '000001', 'FG_ID' => 'OLD']), $request->attributes->get('overviewFlow')['returnUrl']);

            return redirect()->route('product.view', ['materialId' => 'NEWID']);
        });
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $details);
        $this->assertSame('NEWID', $details['materialId']);
        $this->assertSame($query['overviewToken'], $details['overviewToken']);
    }
}
