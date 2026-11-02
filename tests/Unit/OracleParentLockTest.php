<?php

namespace Tests\Unit;

use App\Services\BomContextService;
use App\Services\ExistingComponentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Yajra\Oci8\Oci8Connection;

class OracleParentLockTest extends TestCase
{
    private Oci8Connection $oracle;

    protected function setUp(): void
    {
        parent::setUp();
        // Compile actual service queries with the installed Oracle driver, without OCI/network.
        $this->oracle = new class(fn () => throw new \RuntimeException('No database connection allowed')) extends Oci8Connection {
            public array $queries = [];
            public function getSchemaBuilder(): \Yajra\Oci8\Schema\OracleBuilder
            {
                $schema = \Mockery::mock(\Yajra\Oci8\Schema\OracleBuilder::class);
                $schema->shouldReceive('hasTable')->andReturnUsing(fn ($table) => !str_ends_with($table, '_EXIST'));
                return $schema;
            }
            public ?string $duplicateTable = null;
            public ?string $missingTable = null;
            public function select($query, $bindings = [], $useReadPdo = true)
            {
                $this->queries[] = [$query, $bindings];
                preg_match('/from "([^"]+)"/i', $query, $match);
                $table = $match[1];
                if ($table === $this->missingTable) return [];
                $row = match ($table) {
                    'PROJ1_2_DML_FG_MATTYPE_1' => ['FG_BOM_ID' => 'BFG', 'STATUS_ROW' => 'INS'],
                    'PROJ1_2_DML_SEMI_L1_BOM' => ['SEMI_FG_LV1_BOM_ID' => 'BL1', 'SEMI_FG_LV2_BOM_ID' => 'BL2'],
                    'PROJ1_2_DML_SEMI_L1_ID' => ['SEMI_FG_LV1_ID' => 'L1'],
                    'PROJ1_2_DML_SEMI_L2_BOM' => ['SEMI_FG_LV2_BOM_ID' => 'BL2'],
                    'PROJ1_2_DML_SEMI_L2_ID' => ['SEMI_FG_LV2_ID' => 'L2'],
                    'PROJ1_2_DML_SEMI_L1_COMP_M4' => ['MATERIAL_ID_M4' => 'M4'],
                    default => throw new \RuntimeException('Unexpected table '.$table),
                };
                return array_fill(0, $table === $this->duplicateTable ? 2 : 1, (object) $row);
            }
        };
        DB::shouldReceive('connection')->with('oracle')->andReturn($this->oracle);

    }

    public static function scopes(): array { return [['semi1'], ['semi2'], ['raw4']]; }

    #[DataProvider('scopes')]
    public function test_locked_parent_queries_use_base_tables_without_pagination(string $scope): void
    {
        $context = app(ExistingComponentService::class)->context($scope, [
            'fgMaterialId' => 'FG', 'fgBomId' => 'BFG', 'semiFgLv1BomId' => 'BL1',
            'semiFgLv2BomId' => 'BL2', 'masterMaterialId' => 'M4',
        ], true);
        $this->assertTrue($context['canWrite']);
        $this->assertCount($scope === 'semi2' ? 3 : ($scope === 'raw4' ? 6 : 5), $this->oracle->queries);
        foreach ($this->oracle->queries as [$sql]) {
            $this->assertStringEndsWith('for update', strtolower(trim($sql)));
            $this->assertStringNotContainsString('rownum', strtolower($sql));
            $this->assertStringNotContainsString('from (', strtolower($sql));
        }
        $bomQuery = $this->oracle->queries[1];
        $this->assertSame($scope === 'semi2' ? ['BFG', 'BL2'] : ['BFG', 'BL1', 'BL2'], $bomQuery[1]);
    }

    public function test_unlocked_parent_queries_keep_bounded_ambiguity_checks(): void
    {
        app(BomContextService::class)->level(1, 'BFG', 'BL1', false, false, 'BL2');
        foreach ($this->oracle->queries as [$sql]) {
            $this->assertStringContainsString('rownum', strtolower($sql));
            $this->assertStringNotContainsString('for update', strtolower($sql));
        }
    }

    public static function duplicates(): array
    {
        return array_map(fn ($table) => [$table], ['PROJ1_2_DML_FG_MATTYPE_1', 'PROJ1_2_DML_SEMI_L1_BOM', 'PROJ1_2_DML_SEMI_L1_ID', 'PROJ1_2_DML_SEMI_L1_COMP_M4']);
    }

    #[DataProvider('duplicates')]
    public function test_locked_ambiguous_parent_is_rejected(string $table): void
    {
        $this->oracle->duplicateTable = $table;
        $this->expectException(ValidationException::class);
        app(ExistingComponentService::class)->context('raw4', ['fgMaterialId' => 'FG', 'fgBomId' => 'BFG', 'semiFgLv1BomId' => 'BL1', 'semiFgLv2BomId' => 'BL2', 'masterMaterialId' => 'M4'], true);
    }

    public function test_missing_locked_parent_does_not_resolve_to_another_bom(): void
    {
        $this->oracle->missingTable = 'PROJ1_2_DML_SEMI_L1_BOM';
        $this->assertNull(app(BomContextService::class)->level(1, 'BFG', 'MISSING', true, false, 'BL2'));
    }
}
