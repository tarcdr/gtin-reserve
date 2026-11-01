<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackMaterialCreateRequest;
use App\Http\Requests\PackMaterialDeleteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\Inertia;
use App\Services\ExistingComponentService;
use App\Models\Proj12DmlSemiL1CompM4;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemiFgLv1BomController extends PackMaterialController
{
  private function ownerContext(Request $request, bool $lock = false): array
  {
    $fgMaterialId = trim((string) ($request->fgMaterialId ?: $request->referentMaterialId));
    $fgBomId = trim((string) ($request->fgBomId ?: ($this->resolveFgBomByMaterialId($fgMaterialId)['fgBomId'] ?? '')));
    $levelBomId = trim((string) ($request->semiFgLvBomId ?: $request->semiFgLv1BomId ?: $request->bomId));
    if ($levelBomId === '') {
      $levelBomId = $this->loadSemiFgLv1BomByMaterialId($request->levelMaterialId ?: $request->materialId)['semiFgLvBomId'] ?? '';
    }
    return app(ExistingComponentService::class)->context('semi1', [
      'fgMaterialId' => $fgMaterialId, 'fgBomId' => $fgBomId, 'semiFgLv1BomId' => $levelBomId,
    ], $lock);
  }

  private function component(array $context, string $componentId, bool $lock = false): ?array
  {
    $query = Proj12DmlSemiL1CompM4::query()
      ->whereRaw('TRIM(SEMI_FG_LV1_BOM_ID) = ?', [$context['semiFgLv1BomId']])
      ->whereRaw('TRIM(MATERIAL_ID_M4) = ?', [$componentId]);
    $row = ($lock ? $query->lockForUpdate() : $query)->first();
    return $row ? array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_change_key_case($row->getAttributes(), CASE_UPPER)) : null;
  }

  private function canChange(Request $request, array $context, array $component): bool
  {
    return $context['canWrite'] && ($component['STATUS_ROW'] ?? '') === 'INS'
      && ($component['USER_CREATE'] ?? '') === $request->user()?->user_login;
  }

  private function detailParameters(array $context, string $componentId, string $mode = 'view'): array
  {
    return ['actionMode' => $mode, 'fgMaterialId' => $context['fgMaterialId'],
      'referentMaterialId' => $context['fgMaterialId'], 'fgBomId' => $context['fgBomId'],
      'levelMaterialId' => $context['levelMaterialId'], 'semiFgLvBomId' => $context['semiFgLv1BomId'],
      'semiFgLv2BomId' => $context['semiFgLv2BomId'], 'componentId' => $componentId];
  }

  public function new(Request $request): Response
  {
    $mode = $request->validate(['actionMode' => ['nullable', 'in:create,view,edit,delete']])['actionMode'] ?? 'create';
    $request->merge(['ownerLevel' => 'semiFgLv1']);
    if ($mode === 'create') return parent::new($request);

    $context = $this->ownerContext($request);
    $component = $this->component($context, trim((string) $request->componentId));
    abort_unless($component, 404);
    $canChange = $this->canChange($request, $context, $component);
    if ($mode !== 'view' && !$canChange) abort(403);
    $productSubCat = substr($component['MATERIAL_ID_M4'], 0, 4);
    $subMattype = DB::connection('oracle')->table('PROJ1_2_COMP_SEMI_L1_V')
      ->whereRaw('TRIM(CODE_SUB_CAT) = ?', [$productSubCat])->value('SUB_MATTYPE');
    $InputData = array_merge($context, [
      'ownerLevel' => 'semiFgLv1', 'actionMode' => $mode, 'canEdit' => $canChange, 'canDelete' => $canChange,
      'backRoute' => 'material-levels.semi-fg-lv1.new', 'backMaterialId' => $context['levelMaterialId'],
      'materialId' => $context['levelMaterialId'], 'referentMaterialId' => $context['fgMaterialId'],
      'bomId' => $context['semiFgLv1BomId'], 'bomDesc' => $context['semiFgLv1BomDesc'],
      'semiFgLvBomId' => $context['semiFgLv1BomId'], 'semiFgLvBomDesc' => $context['semiFgLv1BomDesc'],
      'mattype' => '4', 'subMattype' => trim((string) $subMattype),
      'productCat' => substr($productSubCat, 0, 2), 'productSubCat' => $productSubCat,
      'componentId' => $component['MATERIAL_ID_M4'], 'searchDesc' => $component['SEARCH_DESCRIPTION'] ?? '',
      'fullDescEn' => $component['FULL_DESCRIPTION_EN'] ?? '', 'fullDescTh' => $component['FULL_DESCRIPTION_TH'] ?? '',
      'uom' => $component['UOM'] ?? '', 'site' => $component['SITE'] ?? '', 'statusRow' => $component['STATUS_ROW'] ?? '',
    ]);
    return Inertia::render('PackMaterial/SemiFgLv1Bom', ['InputData' => $InputData, 'subMattypes' => $this->packSubMattypesList, 'uoms' => $this->uoms]);
  }

  public function save(PackMaterialCreateRequest $request): RedirectResponse
  {
    $request->validate(['componentId' => ['required', 'string'], 'actionMode' => ['required', 'in:create,edit']]);
    $componentId = trim((string) $request->componentId);
    $context = DB::connection('oracle')->transaction(function () use ($request, $componentId) {
      $context = $this->ownerContext($request, true);
      app(ExistingComponentService::class)->requireWritable($context);
      $component = $this->component($context, $componentId, true);
      if ($request->actionMode === 'edit') {
        abort_unless($component, 404);
        if (!$this->canChange($request, $context, $component)) abort(403);
        $subMattype = DB::connection('oracle')->table('PROJ1_2_COMP_SEMI_L1_V')
          ->whereRaw('TRIM(CODE_SUB_CAT) = ?', [substr($componentId, 0, 4)])->value('SUB_MATTYPE');
        if ($request->mattype !== '4' || $subMattype === null || $request->subMattype !== trim((string) $subMattype)
          || $request->productSubCat !== substr($componentId, 0, 4) || $request->productCat !== substr($componentId, 0, 2)) {
          throw ValidationException::withMessages(['productSubCat' => 'เปลี่ยน category ของ component เดิมไม่ได้']);
        }
      } elseif ($component) {
        throw ValidationException::withMessages(['componentId' => 'Component นี้มีอยู่แล้ว']);
      }
      $this->saveSemiFgLv1ComponentBom(array_merge($request->all(), $context, [
        'ownerLevel' => 'semiFgLv1', 'semiFgLvBomId' => $context['semiFgLv1BomId'], 'componentId' => $componentId,
      ]), $request->user()?->user_login, $request->user()?->role);
      return $context;
    });
    return Redirect::route('packmaterial.semi-fg-lv1-bom.new', $this->detailParameters($context, $componentId));
  }

  public function delete(PackMaterialDeleteRequest $request): RedirectResponse
  {
    $debug = $this->deleteSemiFgLv1(array_merge($request->validated(), [
      'ownerLevel' => 'semiFgLv1',
    ]), $request->user()?->user_login, $request->user()?->role);

    if ($request->get('backRoute') === 'product.view') {
      return Redirect::route('product.view', [
        'materialId' => $request->get('backMaterialId')
          ?: $request->get('referentMaterialId')
          ?: $request->get('fgMaterialId'),
      ])->with('message', 'Delete procedure executed.')
        ->with('deleteDebug', $debug);
    }

    return Redirect::route('material-levels.semi-fg-lv1.new', [
      'mode' => 'view',
      'levelMaterialId' => $request->get('levelMaterialId')
        ?: $request->get('backMaterialId')
        ?: $request->get('materialId'),
    ])->with('message', 'Delete procedure executed.')
      ->with('deleteDebug', $debug);
  }

  public function deleteComponent(PackMaterialDeleteRequest $request): RedirectResponse
  {
    $request->validate(['componentId' => ['required', 'string']]);
    [$context, $debug] = DB::connection('oracle')->transaction(function () use ($request) {
      $context = $this->ownerContext($request, true);
      $component = $this->component($context, trim((string) $request->componentId), true);
      abort_unless($component, 404);
      if (!$this->canChange($request, $context, $component)) abort(403);
      $debug = $this->deleteSemiFgLv1ComponentBom(array_merge($request->validated(), $context, [
        'ownerLevel' => 'semiFgLv1', 'semiFgLvBomId' => $context['semiFgLv1BomId'],
      ]), $request->user()?->user_login, $request->user()?->role);
      return [$context, $debug];
    });
    return Redirect::route('material-levels.semi-fg-lv1.new', [
      'mode' => 'view', 'levelMaterialId' => $context['levelMaterialId'],
      'fgMaterialId' => $context['fgMaterialId'], 'referentMaterialId' => $context['fgMaterialId'],
      'fgBomId' => $context['fgBomId'], 'levelBomId' => $context['semiFgLv1BomId'],
      'parentLevelBomId' => $context['semiFgLv2BomId'],
    ])->with('message', 'Delete procedure executed.')->with('deleteDebug', $debug);
  }
}
