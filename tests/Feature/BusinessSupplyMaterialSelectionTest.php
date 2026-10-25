<?php

namespace Tests\Feature;

use App\Http\Controllers\BusinessSupplyController;
use App\Http\Requests\BusinessSupplyMaterialIdSaveRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BusinessSupplyMaterialSelectionTest extends TestCase
{
    private BusinessSupplyController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.oracle' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('oracle');
        DB::connection('oracle')->getSchemaBuilder()->create('PROJ1_2_BS_LIST_COMP_MATID_V', function (Blueprint $table) {
            foreach (['LIST_BRAND', 'LIST_MAT_TYPE', 'LIST_SUB_TYPE', 'LIST_MAT_ID', 'MATERIAL_ID', 'SEARCH_DESC', 'MATERIAL_DESC_EN', 'MATERIAL_DESC_TH'] as $column) {
                $table->string($column)->nullable();
            }
        });
        foreach ([['BR', '1', '0', 'FG-BR'], ['OTHER', '1', '1', 'FG-OTHER'], [null, '5', '2', 'RAW'], ['BR', '7', '3', 'OTHER-TYPE']] as [$brand, $type, $subtype, $id]) {
            DB::connection('oracle')->table('PROJ1_2_BS_LIST_COMP_MATID_V')->insert([
                'LIST_BRAND' => $brand, 'LIST_MAT_TYPE' => $type, 'LIST_SUB_TYPE' => $subtype,
                'LIST_MAT_ID' => $id, 'MATERIAL_ID' => $id, 'SEARCH_DESC' => $id,
            ]);
        }
        // Avoid unrelated master-data queries in the controller constructor.
        $this->controller = new class extends BusinessSupplyController {
            public function __construct() {}

            public function selections(string $type, string $brand = ''): array
            {
                return $this->businessSupplyMaterialIdSelectionOptions($type, $brand);
            }

            public function materials(array $filters): array
            {
                return $this->loadBusinessSupplyMaterialIdOptions([], new Request($filters));
            }
        };
    }

    public function test_type_one_requires_brand_and_filters_subtypes_and_materials(): void
    {
        $options = $this->controller->selections('1');
        $this->assertSame(['BR', 'OTHER'], array_column($options['brands'], 'value'));
        $this->assertSame([], $options['subMattypes']);
        $this->assertSame(['0'], array_column($this->controller->selections('1', 'BR')['subMattypes'], 'code'));
        $this->assertSame([], $this->controller->materials(['matType' => '1', 'subMatType' => '0']));
        $this->assertSame(['FG-BR'], array_column($this->controller->materials(['matType' => '1', 'brand' => 'BR', 'subMatType' => '0']), 'value'));
    }

    public function test_type_five_loads_without_brand_and_ignores_a_stale_brand(): void
    {
        $options = $this->controller->selections('5', 'BR');
        $this->assertSame([], $options['brands']);
        $this->assertSame(['2'], array_column($options['subMattypes'], 'code'));
        foreach (['', 'BR'] as $brand) {
            $this->assertSame(['RAW'], array_column($this->controller->materials(['matType' => '5', 'brand' => $brand, 'subMatType' => '2']), 'value'));
        }
    }

    public function test_unsupported_type_is_not_available(): void
    {
        $this->assertSame(['brands' => [], 'subMattypes' => []], $this->controller->selections('7', 'BR'));
        $this->assertSame([], $this->controller->materials(['matType' => '7', 'brand' => 'BR', 'subMatType' => '3']));
    }

    public function test_save_validation_requires_brand_only_for_type_one(): void
    {
        $rules = (new BusinessSupplyMaterialIdSaveRequest)->rules();
        $data = ['bomBsId' => 'B1', 'matType' => '5', 'subMatType' => '2', 'componentId' => 'RAW'];
        $this->assertTrue(Validator::make($data, $rules)->passes());
        $data['matType'] = '1';
        $this->assertTrue(Validator::make($data, $rules)->errors()->has('brand'));
        $data['brand'] = 'BR';
        $this->assertTrue(Validator::make($data, $rules)->passes());
        $data['matType'] = '7';
        $this->assertTrue(Validator::make($data, $rules)->errors()->has('matType'));
    }
}
