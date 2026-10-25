<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ExistingComponentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExistingComponentsTest extends TestCase
{
    private ExistingComponentService $service;
    private array $fgInput = ['fgMaterialId' => 'FG1', 'fgBomId' => 'BFG1', 'mattype' => '5', 'subMattype' => '0', 'componentId' => 'PACK1'];
    private array $rawInput = ['fgMaterialId' => 'FG1', 'fgBomId' => 'BFG1', 'semiFgLv1BomId' => 'BL1', 'masterMaterialId' => 'MASTER4', 'componentId' => 'RAW4'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('oracle');
        $tables = [
            'USER_OBJECTS' => ['OBJECT_NAME', 'STATUS'],
            'PROJ1_2_DML_FG_MATTYPE_1' => ['MATERIAL_ID_FG_1', 'FG_BOM_ID', 'DESC_FG_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_FG_BOM' => ['MATERIAL_ID_FG_1', 'FG_BOM_ID', 'DESC_FG_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_FG_COMP_MATID_LS' => ['M5_ID', 'M5_DESCRIPTION'],
            'PROJ1_2_FG_COMP_MATID_MATYP_LS' => ['MATTYPE'],
            'PROJ1_2_FG_COMP_MATID_SUBMT_LS' => ['SUB_MATTYPE', 'DESC_PROD_CAT'],
            'PROJ1_2_MASTER_MATERIAL' => ['MATERIAL_ID', 'MAT_TYPE', 'SUB_TYPE'],
            'PROJ1_2_DML_FG_COMP_MAT_ID' => ['NO', 'FG_BOM_ID', 'COMPONENT_MAT_ID', 'COMP_MAT_ID_DESCRIPTION', 'STATUS_ROW', 'USER_CREATE'],
            'PROJ1_2_DML_SEMI_L1_BOM' => ['FG_BOM_ID', 'SEMI_FG_LV1_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L2_ID' => ['FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'SEMI_FG_LV2_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L2_BOM' => ['FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_ID' => ['FG_BOM_ID', 'SEMI_FG_LV1_BOM_ID', 'SEMI_FG_LV1_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_COMP_M4' => ['SEMI_FG_LV1_BOM_ID', 'MATERIAL_ID_M4', 'SEARCH_DESCRIPTION', 'STATUS_ROW'],
            'PROJ1_2_COMP_M4_MAT_ID_M4_LS' => ['M4_COMP_M4_ID', 'M4_COMP_M4_DESC'],
            'PROJ1_2_DML_SEMI_L1_M4_COMP_M4' => ['SEMI_FG_LV1_BOM_ID', 'MASTER_MATERIAL_ID_M4', 'COMP_M4_MATERIAL_ID_M4', 'SEARCH_DESCRIPTION', 'STATUS_ROW', 'USER_CREATE'],
            'PROJ1_2_DML_SEMI_L1_M4_COMP_ID' => ['NO', 'STORE_MATERIAL_ID_M4', 'STORE_MATERIAL_ID_M4_DESC', 'FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'SEMI_FG_LV1_BOM_ID', 'MASTER_MATERIAL_ID_M4', 'STATUS_ROW', 'USER_CREATE'],
            'PROJ1_2_TRN_LOG' => ['TABLE_NAME_TRN', 'TYPE_DML_TRN', 'USER_DML_TRN', 'USER_ROLE_DML_TRN', 'TIME_DML_TRN'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection('oracle')->getSchemaBuilder()->create($table, function (Blueprint $schema) use ($columns) {
                foreach ($columns as $column) $schema->string($column)->nullable();
            });
        }
        foreach (array_merge(array_keys($tables), ['PROJ1_2_SAVE_COMP_BOMFG_ID', 'PROJ1_2_SAVE_COMP_BOM_SL1_M4ID', 'PROJ1_2_SAVE_INSUPD_ETS']) as $object) {
            $this->insert('USER_OBJECTS', ['OBJECT_NAME' => $object, 'STATUS' => 'VALID']);
        }
        $this->insert('PROJ1_2_DML_FG_MATTYPE_1', ['MATERIAL_ID_FG_1' => 'FG1', 'FG_BOM_ID' => 'BFG1', 'DESC_FG_BOM_ID' => 'FG description', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_FG_COMP_MATID_MATYP_LS', ['MATTYPE' => '5']);
        $this->insert('PROJ1_2_FG_COMP_MATID_SUBMT_LS', ['SUB_MATTYPE' => '0', 'DESC_PROD_CAT' => 'Packaging']);
        foreach ([['PACK1', '50', '01'], ['PACK2', '51', '01'], ['WRONG4', '40', '01']] as [$id, $type, $subtype]) {
            $this->insert('PROJ1_2_FG_COMP_MATID_LS', ['M5_ID' => $id, 'M5_DESCRIPTION' => 'Description '.$id]);
            $this->insert('PROJ1_2_MASTER_MATERIAL', ['MATERIAL_ID' => $id, 'MAT_TYPE' => $type, 'SUB_TYPE' => $subtype]);
        }
        $this->insert('PROJ1_2_DML_SEMI_L1_BOM', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV1_BOM_ID' => 'BL1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L2_BOM', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L2_ID', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'SEMI_FG_LV2_ID' => 'L2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L1_ID', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV1_BOM_ID' => 'BL1', 'SEMI_FG_LV1_ID' => 'L1', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L1_COMP_M4', ['SEMI_FG_LV1_BOM_ID' => 'BL1', 'MATERIAL_ID_M4' => 'MASTER4', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_COMP_M4_MAT_ID_M4_LS', ['M4_COMP_M4_ID' => 'RAW4', 'M4_COMP_M4_DESC' => 'Raw description']);
        $this->service = new class extends ExistingComponentService {
            public array $calls = [];
            protected function execute(string $procedure, array $bindings): void
            {
                $this->calls[] = [$procedure, $bindings];
                // Simulate only the known database procedure insert; never contact Oracle in these tests.
                if ($procedure === 'PROJ1_2_SAVE_COMP_BOMFG_ID') {
                    DB::connection('oracle')->table('PROJ1_2_DML_FG_COMP_MAT_ID')->insert([
                        'NO' => '1', 'FG_BOM_ID' => $bindings['P_BOM_FG_ID'], 'COMPONENT_MAT_ID' => $bindings['P_COMPONENT_MAT_ID'],
                        'COMP_MAT_ID_DESCRIPTION' => $bindings['P_COMP_MAT_ID_DESCRIPTION'], 'STATUS_ROW' => 'INS', 'USER_CREATE' => $bindings['P_USER'],
                    ]);
                }
            }
        };
        $this->app->instance(ExistingComponentService::class, $this->service);
        $this->actingAs(new User(['user_login' => 'tester', 'role' => 'user', 'is_active' => true]));
    }

    private function insert(string $table, array $row): void
    {
        DB::connection('oracle')->table($table)->insert($row);
    }

    public function test_fg_form_and_filtered_options_use_existing_views_and_master_attributes(): void
    {
        $this->get(route('existing-components.index', ['scope' => 'fg'] + $this->fgInput))->assertOk();
        $this->getJson(route('existing-components.options', ['scope' => 'fg'] + $this->fgInput))
            ->assertOk()->assertJsonPath('options.0.value', 'PACK1')->assertJsonCount(1, 'options');
        $this->assertSame([], $this->service->options('fg', ['mattype' => '4', 'subMattype' => '0']));
    }

    public function test_save_uses_master_description_and_rejects_duplicates(): void
    {
        $this->post(route('existing-components.save', ['scope' => 'fg']), $this->fgInput + ['description' => 'FORGED'])->assertRedirect();
        $this->assertSame('PROJ1_2_SAVE_COMP_BOMFG_ID', $this->service->calls[0][0]);
        $this->assertSame('Description PACK1', $this->service->calls[0][1]['P_COMP_MAT_ID_DESCRIPTION']);
        $this->post(route('existing-components.save', ['scope' => 'fg']), $this->fgInput)->assertSessionHasErrors('componentId');
        $this->assertCount(1, $this->service->calls);
    }

    public function test_forged_component_and_foreign_parent_are_rejected_before_save(): void
    {
        $input = array_replace($this->fgInput, ['componentId' => 'WRONG4']);
        $this->post(route('existing-components.save', ['scope' => 'fg']), $input)->assertSessionHasErrors('componentId');
        $this->post(route('existing-components.save', ['scope' => 'fg']), array_replace($this->fgInput, ['fgBomId' => 'FOREIGN']))->assertNotFound();
        $this->assertCount(0, $this->service->calls);
    }

    public function test_completed_fg_cannot_save_or_delete(): void
    {
        $this->service->save('fg', $this->fgInput, 'tester', 'user');
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->update(['STATUS_ROW' => 'COM']);
        $this->post(route('existing-components.save', ['scope' => 'fg']), $this->fgInput)->assertSessionHasErrors('componentId');
        $this->delete(route('existing-components.delete', ['scope' => 'fg']), $this->fgInput + ['no' => '1'])->assertSessionHasErrors('componentId');
        $this->assertSame(1, DB::connection('oracle')->table('PROJ1_2_DML_FG_COMP_MAT_ID')->count());
    }

    public function test_legacy_separate_bom_is_resolved_and_its_exported_status_blocks_save(): void
    {
        DB::connection('oracle')->table('PROJ1_2_DML_FG_MATTYPE_1')->update(['FG_BOM_ID' => null]);
        $this->insert('PROJ1_2_DML_FG_BOM', ['MATERIAL_ID_FG_1' => 'FG1', 'FG_BOM_ID' => 'BFG1', 'STATUS_ROW' => 'INS']);
        $this->assertTrue($this->service->context('fg', $this->fgInput)['canWrite']);
        DB::connection('oracle')->table('PROJ1_2_DML_FG_BOM')->update(['STATUS_ROW' => 'ETS']);
        $this->post(route('existing-components.save', ['scope' => 'fg']), $this->fgInput)->assertSessionHasErrors('componentId');
        $this->assertCount(0, $this->service->calls);
    }

    public function test_delete_removes_only_owned_link_and_writes_audit_log(): void
    {
        $this->service->save('fg', $this->fgInput, 'tester', 'user');
        $this->insert('PROJ1_2_DML_FG_COMP_MAT_ID', ['NO' => '2', 'FG_BOM_ID' => 'OTHER', 'COMPONENT_MAT_ID' => 'PACK1', 'USER_CREATE' => 'tester', 'STATUS_ROW' => 'INS']);
        $this->delete(route('existing-components.delete', ['scope' => 'fg']), $this->fgInput + ['no' => '1'])->assertRedirect();
        $this->assertSame(['OTHER'], DB::connection('oracle')->table('PROJ1_2_DML_FG_COMP_MAT_ID')->pluck('FG_BOM_ID')->all());
        $this->assertSame(1, DB::connection('oracle')->table('PROJ1_2_TRN_LOG')->count());
        $this->assertSame(3, DB::connection('oracle')->table('PROJ1_2_MASTER_MATERIAL')->count());
    }

    public function test_delete_rejects_other_creator_and_completed_link(): void
    {
        $this->service->save('fg', $this->fgInput, 'someone-else', 'user');
        $url = route('existing-components.delete', ['scope' => 'fg']);
        $this->delete($url, $this->fgInput + ['no' => '1'])->assertSessionHasErrors('componentId');
        DB::connection('oracle')->table('PROJ1_2_DML_FG_COMP_MAT_ID')->update(['USER_CREATE' => 'tester', 'STATUS_ROW' => 'COM']);
        $this->delete($url, $this->fgInput + ['no' => '1'])->assertSessionHasErrors('componentId');
        $this->assertSame(1, DB::connection('oracle')->table('PROJ1_2_DML_FG_COMP_MAT_ID')->count());
    }

    public function test_invalid_procedure_disables_flow_and_blocks_direct_post(): void
    {
        DB::connection('oracle')->table('USER_OBJECTS')->where('OBJECT_NAME', 'PROJ1_2_SAVE_COMP_BOMFG_ID')->update(['STATUS' => 'INVALID']);
        $this->assertFalse($this->service->availability('fg')['ready']);
        $this->post(route('existing-components.save', ['scope' => 'fg']), $this->fgInput)->assertSessionHasErrors('componentId');
        $this->assertCount(0, $this->service->calls);
    }

    public function test_raw_material_existing_uses_verified_full_parent_path(): void
    {
        $this->post(route('existing-components.save', ['scope' => 'raw4']), $this->rawInput)->assertRedirect();
        [$procedure, $bindings] = $this->service->calls[0];
        $this->assertSame('PROJ1_2_SAVE_COMP_BOM_SL1_M4ID', $procedure);
        $this->assertSame(['BFG1', 'BL2', 'BL1', 'MASTER4', 'RAW4'], array_map(fn ($key) => $bindings[$key], ['P_FG_BOM_ID', 'P_SEMI_FG_LV2_BOM_ID', 'P_SEMI_FG_LV1_BOM_ID', 'P_MASTER_MATERIAL_ID_M4', 'P_STORE_MATERIAL_ID_M4']));
        $this->post(route('existing-components.save', ['scope' => 'raw4']), array_replace($this->rawInput, ['masterMaterialId' => 'FOREIGN']))->assertNotFound();
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L2_BOM')->update(['STATUS_ROW' => 'COM']);
        $this->post(route('existing-components.save', ['scope' => 'raw4']), $this->rawInput)->assertSessionHasErrors('componentId');
        $this->assertCount(1, $this->service->calls);
    }

    public function test_raw_delete_is_scoped_to_master_and_description_comes_from_material_list(): void
    {
        foreach (['MASTER4', 'OTHER4'] as $index => $master) {
            $this->insert('PROJ1_2_DML_SEMI_L1_M4_COMP_ID', ['NO' => (string) ($index + 1), 'FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'SEMI_FG_LV1_BOM_ID' => 'BL1', 'MASTER_MATERIAL_ID_M4' => $master, 'STORE_MATERIAL_ID_M4' => 'RAW4', 'STATUS_ROW' => 'INS', 'USER_CREATE' => 'tester']);
        }
        $this->getJson(route('existing-components.options', ['scope' => 'raw4'] + $this->rawInput))->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.label', 'Raw description');
        $this->delete(route('existing-components.delete', ['scope' => 'raw4']), $this->rawInput + ['no' => '1'])->assertRedirect();
        $this->assertSame(['OTHER4'], DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_M4_COMP_ID')->pluck('MASTER_MATERIAL_ID_M4')->all());
    }
}
