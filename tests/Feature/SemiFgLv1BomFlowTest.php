<?php

namespace Tests\Feature;

use App\Http\Controllers\SemiFgLv1BomController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SemiFgLv1BomFlowTest extends TestCase
{
    private array $input = ['actionMode' => 'create', 'fgMaterialId' => 'FG1', 'fgBomId' => 'BFG1', 'semiFgLvBomId' => 'BL1', 'levelMaterialId' => 'WRONG-SUBMITTED-ID', 'componentId' => '400200004', 'mattype' => '4', 'subMattype' => '0', 'productCat' => '40', 'productSubCat' => '4002', 'searchDesc' => 'Saved component', 'fullDescEn' => 'English', 'fullDescTh' => 'Thai', 'uom' => 'DAY'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('oracle');
        foreach ([
            'PROJ1_2_DML_FG_MATTYPE_1' => ['MATERIAL_ID_FG_1', 'FG_BOM_ID', 'DESC_FG_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_BOM' => ['FG_BOM_ID', 'SEMI_FG_LV1_BOM_ID', 'DESC_SEMI_FG_LV1_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_ID' => ['FG_BOM_ID', 'SEMI_FG_LV1_BOM_ID', 'SEMI_FG_LV1_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L2_BOM' => ['FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'DESC_SEMI_FG_LV2_BOM_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L2_ID' => ['FG_BOM_ID', 'SEMI_FG_LV2_BOM_ID', 'SEMI_FG_LV2_ID', 'STATUS_ROW'],
            'PROJ1_2_DML_SEMI_L1_COMP_M4' => ['SEMI_FG_LV1_BOM_ID', 'MATERIAL_ID_M4', 'SEARCH_DESCRIPTION', 'FULL_DESCRIPTION_EN', 'FULL_DESCRIPTION_TH', 'UOM', 'STATUS_ROW', 'USER_CREATE', 'SITE'],
            'PROJ1_2_COMP_SEMI_L1_V' => ['CODE_SUB_CAT', 'SUB_MATTYPE'],
        ] as $table => $columns) {
            DB::connection('oracle')->getSchemaBuilder()->create($table, function (Blueprint $schema) use ($columns) {
                foreach ($columns as $column) $schema->string($column)->nullable();
            });
        }
        $this->insert('PROJ1_2_DML_FG_MATTYPE_1', ['MATERIAL_ID_FG_1' => 'FG1', 'FG_BOM_ID' => 'BFG1', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L1_BOM', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV1_BOM_ID' => 'BL1', 'DESC_SEMI_FG_LV1_BOM_ID' => 'Level one BOM', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L1_ID', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV1_BOM_ID' => 'BL1', 'SEMI_FG_LV1_ID' => 'L1', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L2_BOM', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_DML_SEMI_L2_ID', ['FG_BOM_ID' => 'BFG1', 'SEMI_FG_LV2_BOM_ID' => 'BL2', 'SEMI_FG_LV2_ID' => 'L2', 'STATUS_ROW' => 'INS']);
        $this->insert('PROJ1_2_COMP_SEMI_L1_V', ['CODE_SUB_CAT' => '4002', 'SUB_MATTYPE' => '0']);
        $this->app->instance(SemiFgLv1BomController::class, new class extends SemiFgLv1BomController {
            public function __construct() { $this->uoms = [['code' => 'DAY']]; $this->packSubMattypesList = [['code' => '0']]; }
            protected function saveSemiFgLv1ComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): void
            {
                // Simulate only the existing procedure; all context/ownership/redirect logic is real.
                DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->updateOrInsert([
                    'SEMI_FG_LV1_BOM_ID' => $data['semiFgLvBomId'], 'MATERIAL_ID_M4' => $data['componentId'],
                ], ['SEARCH_DESCRIPTION' => $data['searchDesc'], 'FULL_DESCRIPTION_EN' => $data['fullDescEn'], 'FULL_DESCRIPTION_TH' => $data['fullDescTh'], 'UOM' => $data['uom'], 'USER_CREATE' => $userLogin, 'STATUS_ROW' => 'INS']);
            }
            protected function deleteSemiFgLv1ComponentBom(array $data, ?string $userLogin = null, ?string $userRole = null): array
            {
                DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->where('SEMI_FG_LV1_BOM_ID', $data['semiFgLvBomId'])->where('MATERIAL_ID_M4', $data['componentId'])->delete();
                return [];
            }
        });
        $this->actingAs(new User(['user_login' => 'tester', 'role' => 'user', 'is_active' => true]));
    }

    private function insert(string $table, array $row): void { DB::connection('oracle')->table($table)->insert($row); }
    private function detailUrl(string $mode = 'view'): string
    {
        return route('packmaterial.semi-fg-lv1-bom.new', ['actionMode' => $mode, 'fgMaterialId' => 'FG1', 'referentMaterialId' => 'FG1', 'fgBomId' => 'BFG1', 'levelMaterialId' => 'L1', 'semiFgLvBomId' => 'BL1', 'semiFgLv2BomId' => 'BL2', 'componentId' => '400200004']);
    }
    private function createComponent(): void { $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), $this->input)->assertRedirect($this->detailUrl()); }

    public function test_create_redirects_to_its_own_detail_and_refresh_reads_saved_values(): void
    {
        $this->createComponent();
        for ($i = 0; $i < 2; $i++) {
            $this->get($this->detailUrl().'&searchDesc=FORGED')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PackMaterial/SemiFgLv1Bom')->where('InputData.actionMode', 'view')
                ->where('InputData.searchDesc', 'Saved component')->where('InputData.componentId', '400200004')
                ->where('InputData.levelMaterialId', 'L1')->where('InputData.semiFgLv2BomId', 'BL2')
                ->where('InputData.semiFgLvBomId', 'BL1')->where('InputData.canEdit', true)->where('InputData.canDelete', true));
        }
    }

    public function test_edit_loads_without_writing_and_update_returns_to_the_same_detail(): void
    {
        $this->createComponent();
        $this->get($this->detailUrl('edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('InputData.actionMode', 'edit')->where('InputData.searchDesc', 'Saved component'));
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit', 'searchDesc' => 'Updated']))->assertRedirect($this->detailUrl());
        $this->get($this->detailUrl())->assertInertia(fn (AssertableInertia $page) => $page->where('InputData.searchDesc', 'Updated'));
    }

    public function test_delete_returns_to_owner_and_keeps_another_parents_component(): void
    {
        $this->createComponent();
        $this->insert('PROJ1_2_DML_SEMI_L1_COMP_M4', ['SEMI_FG_LV1_BOM_ID' => 'OTHER', 'MATERIAL_ID_M4' => '400200004']);
        $this->delete(route('packmaterial.semi-fg-lv1-bom.component.delete'), $this->input)->assertRedirect(route('material-levels.semi-fg-lv1.new', [
            'mode' => 'view', 'levelMaterialId' => 'L1', 'fgMaterialId' => 'FG1', 'referentMaterialId' => 'FG1', 'fgBomId' => 'BFG1', 'levelBomId' => 'BL1', 'parentLevelBomId' => 'BL2',
        ]));
        $this->assertSame(['OTHER'], DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->pluck('SEMI_FG_LV1_BOM_ID')->all());
    }

    public function test_foreign_parent_and_missing_components_cannot_load_or_update(): void
    {
        $this->createComponent();
        $this->get($this->detailUrl().'&semiFgLvBomId=FOREIGN')->assertNotFound();
        $this->get($this->detailUrl().'&componentId=MISSING')->assertNotFound();
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit', 'semiFgLvBomId' => 'FOREIGN']))->assertNotFound();
    }

    public function test_completed_component_is_viewable_but_cannot_be_edited_or_deleted(): void
    {
        $this->createComponent();
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->update(['STATUS_ROW' => 'COM']);
        $this->get($this->detailUrl())->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('InputData.canEdit', false)->where('InputData.canDelete', false));
        $this->get($this->detailUrl('edit'))->assertForbidden();
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit']))->assertForbidden();
        $this->delete(route('packmaterial.semi-fg-lv1-bom.component.delete'), $this->input)->assertForbidden();
    }

    public function test_another_creator_cannot_edit_and_locked_category_cannot_change(): void
    {
        $this->createComponent();
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit', 'productSubCat' => '4008']))->assertSessionHasErrors('productSubCat');
        $this->actingAs(new User(['user_login' => 'other', 'role' => 'user', 'is_active' => true]));
        $this->get($this->detailUrl('edit'))->assertForbidden();
        $this->delete(route('packmaterial.semi-fg-lv1-bom.component.delete'), $this->input)->assertForbidden();
    }

    public function test_completed_parent_blocks_mutation_while_detail_remains_available(): void
    {
        $this->createComponent();
        DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L2_BOM')->update(['STATUS_ROW' => 'ETS']);
        $this->get($this->detailUrl())->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('InputData.canEdit', false));
        $this->get($this->detailUrl('edit'))->assertForbidden();
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit']))->assertSessionHasErrors('componentId');
        $this->delete(route('packmaterial.semi-fg-lv1-bom.component.delete'), $this->input)->assertForbidden();
    }

    public function test_view_cannot_submit_and_edit_cannot_change_subtype(): void
    {
        $this->createComponent();
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'view']))->assertSessionHasErrors('actionMode');
        $this->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit', 'subMattype' => '1']))->assertSessionHasErrors('productSubCat');
    }

    public function test_validation_error_stays_on_edit_with_input_and_does_not_write(): void
    {
        $this->createComponent();
        $this->from($this->detailUrl('edit'))->patch(route('packmaterial.semi-fg-lv1-bom.save'), array_replace($this->input, ['actionMode' => 'edit', 'fullDescEn' => '', 'searchDesc' => 'Unsaved']))
            ->assertRedirect($this->detailUrl('edit'))->assertSessionHasErrors('fullDescEn')->assertSessionHasInput('searchDesc', 'Unsaved');
        $this->assertSame('Saved component', DB::connection('oracle')->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->value('SEARCH_DESCRIPTION'));
    }
}
