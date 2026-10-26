<?php

namespace Tests\Unit;

use App\Services\BomContextService;
use App\Services\ExistingComponentService;
use App\Services\ExistingLevelService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExistingLevelParentTest extends TestCase
{
    public static function parentCases(): array
    {
        return [
            'new editable parent' => ['new', 'INS', true],
            'existing parent cannot create level one' => ['existing', 'INS', false],
            'completed new parent' => ['new', 'COM', false],
            'exported new parent' => ['new', 'ETS', false],
        ];
    }

    #[DataProvider('parentCases')]
    public function test_level_one_requires_a_writable_new_level_two(string $source, string $status, bool $writable): void
    {
        $components = $this->createMock(ExistingComponentService::class);
        $components->method('context')->willReturn(['fgBomId' => 'FG-BOM', 'canWrite' => true]);
        $parents = $this->createMock(BomContextService::class);
        $parents->expects($this->once())->method('level')->with(2, 'FG-BOM', 'L2-BOM', false)->willReturn([
            'sourceType' => $source, 'statusRow' => $status, 'idStatus' => 'INS',
            'bomId' => 'L2-BOM', 'bomDesc' => 'Level two', 'id' => 'L2',
        ]);
        $service = new class($components, $parents) extends ExistingLevelService {
            public function availability(int $level): array { return ['ready' => true]; }
        };
        $context = $service->context(1, ['semiFgLv2BomId' => 'L2-BOM']);
        $this->assertSame($writable, $context['canWrite']);
        $this->assertSame('L2-BOM', $context['semiFgLv2BomId']);
    }
}
