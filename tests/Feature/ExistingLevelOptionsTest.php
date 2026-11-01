<?php

namespace Tests\Feature;

use App\Services\BomContextService;
use App\Services\ExistingComponentService;
use App\Services\ExistingLevelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExistingLevelOptionsTest extends TestCase
{
    private ExistingLevelService $service;
    private array $filters = ['brand' => 'SW', 'mattype' => '3', 'subMattype' => '0'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('oracle');
        foreach ([
            'PROJ1_2_SEMI_FG_L1_ID_EXIST_LS' => ['MATERIAL_ID', 'MATERIAL_DESCRIPTION'],
            'PROJ1_MATERIAL' => ['MATERIAL_ID', 'MAT_TYPE', 'SUB_TYPE', 'BRAND'],
            'PROJ1_2_MASTER_MATERIAL' => ['MATERIAL_ID', 'MAT_TYPE', 'SUB_TYPE', 'BRAND'],
            'PROJ1_BRAND' => ['BRAND', 'BRAND_ABB'],
            'PROJ1_2_SEMI_FG_LV1_ID_V' => ['MATERIAL_ID', 'SEARCH_DESCRIPTION', 'MATTYPE', 'SUB_MATTYPE', 'BRAND_ABB', 'BOM_ID', 'BOM_DESCRIPTION', 'STATUS_ROW', 'FULL_DESCRIPTION_EN', 'FULL_DESCRIPTION_TH', 'UOM'],
        ] as $table => $columns) {
            DB::connection('oracle')->getSchemaBuilder()->create($table, function (Blueprint $schema) use ($columns) {
                foreach ($columns as $column) $schema->string($column)->nullable();
            });
        }
        $this->insert('PROJ1_BRAND', ['BRAND' => 'NAMU LIFE SNAILWHITE', 'BRAND_ABB' => 'SW']);
        $this->insert('PROJ1_BRAND', ['BRAND' => 'BEAR', 'BRAND_ABB' => 'BR']);
        $components = $this->createMock(ExistingComponentService::class);
        $components->expects($this->never())->method('callProcedure');
        $this->service = new class($components, $this->createMock(BomContextService::class)) extends ExistingLevelService {
            public function context(int $level, array $input, bool $lock = false): array { return ['fgBomId' => 'FG', 'fgMaterialId' => 'FG1', 'canWrite' => true]; }
        };
        $this->legacy('30SW0001', '3', '0', 'NAMU LIFE SNAILWHITE');
    }

    private function insert(string $table, array $row): void { DB::connection('oracle')->table($table)->insert($row); }
    private function legacy(string $id, string $type, string $subtype, string $brand): void
    {
        $this->insert('PROJ1_2_SEMI_FG_L1_ID_EXIST_LS', ['MATERIAL_ID' => $id, 'MATERIAL_DESCRIPTION' => 'ERP '.$id]);
        $this->insert('PROJ1_2_MASTER_MATERIAL', ['MATERIAL_ID' => $id, 'MAT_TYPE' => $type, 'SUB_TYPE' => $subtype, 'BRAND' => $brand]);
    }
    private function detail(string $id, string $bom, string $brand = 'SW'): void
    {
        $this->insert('PROJ1_2_SEMI_FG_LV1_ID_V', ['MATERIAL_ID' => $id, 'BOM_ID' => $bom, 'SEARCH_DESCRIPTION' => 'Real source', 'MATTYPE' => '3', 'SUB_MATTYPE' => '0', 'BRAND_ABB' => $brand]);
    }

    public function test_legacy_material_without_source_bom_is_visible_but_unselectable(): void
    {
        $option = $this->service->options(1, $this->filters)[0];
        $this->assertSame('30SW0001', $option['materialId']);
        $this->assertFalse($option['selectable']);
        $this->assertSame('ไม่มี BOM ต้นทาง', $option['unavailableReason']);
        $this->assertSame('', $option['bomId']);
    }

    public function test_erp_lov_material_does_not_require_a_second_master_or_a_dml_bom(): void
    {
        $this->insert('PROJ1_2_SEMI_FG_L1_ID_EXIST_LS', ['MATERIAL_ID' => 'ERP-ONLY', 'MATERIAL_DESCRIPTION' => 'ERP description']);
        $this->insert('PROJ1_MATERIAL', ['MATERIAL_ID' => 'ERP-ONLY', 'MAT_TYPE' => '3', 'SUB_TYPE' => '0', 'BRAND' => 'NAMU LIFE SNAILWHITE']);
        $option = collect($this->service->options(1, $this->filters))->firstWhere('materialId', 'ERP-ONLY');
        $this->assertNotNull($option);
        $this->assertFalse($option['selectable']);
        $this->assertSame('ERP description', $option['description']);
    }

    public function test_all_real_boms_are_exposed_and_master_brand_overrides_source_fg_brand(): void
    {
        $this->detail('30SW0001', 'REAL-BOM-1', 'BR');
        $this->detail('30SW0001', 'REAL-BOM-2', 'BR');
        $options = $this->service->options(1, $this->filters);
        $this->assertSame(['REAL-BOM-1', 'REAL-BOM-2'], array_column($options, 'value'));
        $this->assertSame([true, true], array_column($options, 'selectable'));
        $this->assertSame([], $this->service->options(1, array_replace($this->filters, ['brand' => 'BR'])));
    }

    public function test_filter_uses_master_fields_rather_than_material_id_prefix(): void
    {
        $this->legacy('UNRELATED-ID', '30', '09', 'SW');
        $this->legacy('30SW-WRONG-SUB', '3', '1', 'SW');
        $this->legacy('30SW-WRONG-BRAND', '3', '0', 'BEAR');
        $this->assertSame(['30SW0001', 'UNRELATED-ID'], array_column($this->service->options(1, $this->filters), 'materialId'));
    }

    public function test_saved_dml_materials_outside_erp_lov_are_retained(): void
    {
        $this->detail('NEW-DML', 'NEW-DML-BOM');
        $this->assertContains('NEW-DML-BOM', array_column($this->service->options(1, $this->filters), 'value'));
    }

    public function test_submitting_an_unavailable_material_is_rejected_before_procedure_call(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->save(1, $this->filters + ['componentId' => 'unavailable:30SW0001'], 'tester', 'user');
    }

    public function test_submitting_a_bom_for_another_brand_is_rejected(): void
    {
        $this->detail('OTHER', 'OTHER-BOM', 'BR');
        $this->expectException(ValidationException::class);
        $this->service->save(1, $this->filters + ['componentId' => 'OTHER-BOM'], 'tester', 'user');
    }
}
