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
    private $components;
    private array $input = ['mattype' => '3', 'subMattype' => '0', 'fgBomId' => 'FG', 'semiFgLv2BomId' => 'L2'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('oracle');
        foreach ([
            'PROJ1_2_SEMI_FG_LV1_ID_V' => ['MATERIAL_ID', 'SEARCH_DESCRIPTION', 'MATTYPE', 'SUB_MATTYPE', 'BRAND_ABB', 'BOM_ID', 'BOM_DESCRIPTION', 'STATUS_ROW', 'FULL_DESCRIPTION_EN', 'FULL_DESCRIPTION_TH', 'UOM'],
            'PROJ1_2_DML_SEMI_L1_ID_EXIST' => ['SEMI_FG_LV1_ID_EXIST', 'FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'SEMI_FG_LV1_BOM_ID_EXIST'],
            'PROJ1_2_DML_FG_MATTYPE_1' => ['MATERIAL_ID_FG_1', 'SITE'],
        ] as $table => $columns) {
            DB::connection('oracle')->getSchemaBuilder()->create($table, function (Blueprint $schema) use ($columns) {
                foreach ($columns as $column) $schema->string($column)->nullable();
            });
        }
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->insert(['MATERIAL_ID_FG_1' => 'FG1', 'SITE' => 'RK']);
        $this->components = $this->createMock(ExistingComponentService::class);
        $this->service = new class($this->components, $this->createMock(BomContextService::class)) extends ExistingLevelService {
            public function context(int $level, array $input, bool $lock = false): array { return ['fgBomId' => 'FG', 'fgMaterialId' => 'FG1', 'semiFgLv2BomId' => 'L2', 'semiFgLv2Id' => 'L2-ID', 'canWrite' => true]; }
        };
        $this->detail('30SW0001', 'REAL-BOM-1', 'SW');
        $this->detail('30SW0001', 'REAL-BOM-2', 'BR');
        $this->detail('30BS0001', 'B30000008_RK_01', 'BS');
    }

    private function detail(string $id, string $bom, string $brand, string $sub = '0'): void
    {
        DB::connection('oracle')->table('PROJ1_2_SEMI_FG_LV1_ID_V')->insert(['MATERIAL_ID' => $id, 'BOM_ID' => $bom, 'SEARCH_DESCRIPTION' => 'Real source', 'MATTYPE' => '3', 'SUB_MATTYPE' => $sub, 'BRAND_ABB' => $brand, 'UOM' => 'DAY']);
    }
    private function link(string $fg = 'FG', string $l2 = 'L2'): void
    {
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_ID_EXIST')->insert(['SEMI_FG_LV1_ID_EXIST' => '30SW0001', 'FG_BOM_ID' => $fg, 'SEMI_FG_LV2_BOM_ID' => $l2, 'SEMI_FG_LV1_BOM_ID_EXIST' => 'REAL-BOM-1']);
    }

    public function test_lv1_uses_only_detail_view_and_does_not_filter_by_brand(): void
    {
        $options = $this->service->options(1, $this->input);
        $this->assertCount(3, $options);
        $this->assertSame($options, $this->service->options(1, $this->input + ['brand' => 'BS']));
        $this->assertSame([true, true, true], array_column($options, 'selectable'));
    }
    public function test_multiple_boms_of_one_material_remain_separate_choices(): void
    {
        $options = array_filter($this->service->options(1, $this->input), fn ($o) => $o['materialId'] === '30SW0001');
        $this->assertSame(['REAL-BOM-1', 'REAL-BOM-2'], array_column($options, 'value'));
    }
    public function test_linking_one_material_excludes_all_its_boms_in_the_same_parent(): void
    {
        $this->link();
        $this->assertSame(['30BS0001'], array_column($this->service->options(1, $this->input), 'materialId'));
    }
    public function test_another_fg_or_lv2_does_not_hide_the_material(): void
    {
        $this->link('OTHER-FG'); $this->link('FG', 'OTHER-L2');
        $this->assertCount(3, $this->service->options(1, $this->input));
    }
    public function test_subtype_and_canonical_parent_are_required(): void
    {
        $this->detail('31BS0001', 'OTHER-SUB', 'BS', '1');
        $this->assertSame(['OTHER-SUB'], array_column($this->service->options(1, array_replace($this->input, ['subMattype' => '1'])), 'value'));
        $this->assertSame([], $this->service->options(1, ['mattype' => '3', 'subMattype' => '0']));
    }
    public function test_forged_duplicate_selection_is_rejected_before_calling_procedure(): void
    {
        $this->link();
        $this->components->expects($this->never())->method('callProcedure');
        $this->expectException(ValidationException::class);
        $this->service->save(1, $this->input + ['componentId' => 'REAL-BOM-2'], 'tester', 'user');
    }
    public function test_save_rereads_bom_and_material_and_ignores_forged_parent_values(): void
    {
        $this->components->expects($this->once())->method('callProcedure')->with('PROJ1_2_SAVE_BOM_SEMI_L1_EXIST', $this->callback(fn ($p) =>
            $p['P_FG_BOM_ID'] === 'FG' && $p['P_SEMI_FG_L2_BOM_ID'] === 'L2' && $p['P_SEMI_FG_LV1_ID_EXIST'] === '30BS0001' && $p['P_SEMI_FG_L1_BOM_ID_EXIST'] === 'B30000008_RK_01'));
        $this->service->save(1, array_replace($this->input, ['componentId' => 'B30000008_RK_01', 'fgBomId' => 'FORGED', 'semiFgLv2BomId' => 'FORGED']), 'tester', 'user');
    }
}
