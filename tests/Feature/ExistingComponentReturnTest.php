<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ExistingComponentService;
use App\Services\ExistingLevelService;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExistingComponentReturnTest extends TestCase
{
    public static function scopes(): array { return [['semi1', 1], ['semi2', 2], ['fg', null], ['level1', null], ['level2', null]]; }

    #[DataProvider('scopes')]
    public function test_form_save_and_delete_return_to_the_canonical_owner(string $scope, ?int $level): void
    {
        $context = ['fgMaterialId' => 'FG1', 'fgBomId' => 'BFG', 'levelMaterialId' => $level === 1 ? 'L1' : 'L2', 'semiFgLv1BomId' => 'BL1', 'semiFgLv2BomId' => 'BL2', 'canWrite' => true];
        $components = $this->createMock(ExistingComponentService::class);
        $components->method('context')->willReturn($context);
        $components->method('filters')->willReturn([]);
        $levels = $this->createMock(ExistingLevelService::class);
        $levels->method('context')->willReturn($context);
        $levels->method('filters')->willReturn([]);
        $isLevel = str_starts_with($scope, 'level');
        ($isLevel ? $levels : $components)->expects($this->once())->method('save');
        ($isLevel ? $levels : $components)->expects($this->once())->method('delete');
        $this->app->instance(ExistingComponentService::class, $components);
        $this->app->instance(ExistingLevelService::class, $levels);
        $this->actingAs(new User(['user_login' => 'tester', 'role' => 'user', 'is_active' => true]));
        $input = ['fgMaterialId' => 'FG1', 'fgBomId' => 'BFG', 'semiFgLv1BomId' => 'BL1', 'semiFgLv2BomId' => 'BL2', 'componentId' => 'COMP1', 'mattype' => '5', 'subMattype' => '0', 'no' => '1'];
        $expected = $level ? route("material-levels.semi-fg-lv{$level}.new", [
            'mode' => 'view', 'levelMaterialId' => $context['levelMaterialId'], 'referentMaterialId' => 'FG1',
            'fgMaterialId' => 'FG1', 'fgBomId' => 'BFG', 'levelBomId' => "BL{$level}", 'parentLevelBomId' => 'BL2',
        ]) : route('product.view', ['materialId' => 'FG1']);
        $this->get(route('existing-components.index', ['scope' => $scope] + $input))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('ExistingComponents/Select')->where('backUrl', $expected));
        $this->post(route('existing-components.save', ['scope' => $scope]), $input)->assertRedirect($expected);
        $this->delete(route('existing-components.delete', ['scope' => $scope]), $input)->assertRedirect($expected);
    }
    public function test_validation_errors_return_to_the_form_with_the_selection_preserved(): void
    {
        $components = $this->createMock(ExistingComponentService::class);
        $components->expects($this->once())->method('save')->willThrowException(
            \Illuminate\Validation\ValidationException::withMessages(['componentId' => 'รายการนี้บันทึกไม่ได้'])
        );
        $this->app->instance(ExistingComponentService::class, $components);
        $this->actingAs(new User(['user_login' => 'tester', 'role' => 'user', 'is_active' => true]));
        $form = route('existing-components.index', ['scope' => 'semi2', 'fgMaterialId' => 'FG1', 'fgBomId' => 'BFG', 'semiFgLv2BomId' => 'BL2']);
        $this->from($form)->post(route('existing-components.save', ['scope' => 'semi2']), [
            'fgMaterialId' => 'FG1', 'fgBomId' => 'BFG', 'semiFgLv2BomId' => 'BL2',
            'mattype' => '5', 'subMattype' => '0', 'productSubCat' => '5009', 'componentId' => 'PACK1',
        ])->assertRedirect($form)->assertSessionHasErrors('componentId')->assertSessionHasInput('componentId', 'PACK1')->assertSessionHasInput('productSubCat', '5009');
    }

}
