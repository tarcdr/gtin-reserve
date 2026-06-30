<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackMaterialCreateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class SemiFgLv2BomController extends PackMaterialController
{
  public function new(Request $request): Response
  {
    $request->merge(['ownerLevel' => 'semiFgLv2']);

    return parent::new($request);
  }

  public function save(PackMaterialCreateRequest $request): RedirectResponse
  {
    $componentId = $request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    );

    $components = $this->upsertComponent($request->get('components', []), [
      'code' => $componentId,
      'label' => $request->searchDesc ?: $request->fullDescEn ?: $componentId,
      'status' => 'INS',
      'searchDesc' => $request->searchDesc,
      'fullDescEn' => $request->fullDescEn,
      'fullDescTh' => $request->fullDescTh,
      'uom' => $request->uom,
      'productCat' => $request->productCat,
      'productSubCat' => $request->productSubCat,
    ]);

    $this->saveSemiFgLv2ComponentBom(array_merge($request->all(), [
      'ownerLevel' => 'semiFgLv2',
      'componentId' => $componentId,
    ]), $request->user()?->user_login, $request->user()?->role);

    $backRoute = $request->get('backRoute', 'material-levels.semi-fg-lv2.new');
    $backMaterialId = $request->get('backMaterialId') ?: $request->get('levelMaterialId') ?: $request->get('materialId');

    if ($backRoute === 'material-levels.semi-fg-lv2.new') {
      return Redirect::route($backRoute, [
        'mode' => 'view',
        'levelMaterialId' => $backMaterialId,
      ]);
    }

    return Redirect::route($backRoute, [
      'mode' => 'view',
      'levelMaterialId' => $backMaterialId,
      'fgDetail' => $request->get('fgDetail', []),
      'ownerDetail' => $request->get('ownerDetail', []),
      'components' => $components,
    ]);
  }
}
