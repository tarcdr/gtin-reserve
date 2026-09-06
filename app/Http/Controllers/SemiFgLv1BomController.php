<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackMaterialCreateRequest;
use App\Http\Requests\PackMaterialDeleteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class SemiFgLv1BomController extends PackMaterialController
{
  public function new(Request $request): Response
  {
    $request->merge(['ownerLevel' => 'semiFgLv1']);

    return parent::new($request);
  }

  public function save(PackMaterialCreateRequest $request): RedirectResponse
  {
    $componentId = trim((string) ($request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    )));

    $this->saveSemiFgLv1ComponentBom(array_merge($request->all(), [
      'ownerLevel' => 'semiFgLv1',
      'componentId' => $componentId,
    ]), $request->user()?->user_login, $request->user()?->role);

    return Redirect::route('material-levels.semi-fg-lv1.new', [
      'mode' => 'view',
      'levelMaterialId' => $request->get('levelMaterialId')
        ?: $request->get('backMaterialId')
        ?: $request->get('materialId'),
    ]);
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
    $debug = $this->deleteSemiFgLv1ComponentBom(array_merge($request->validated(), [
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
}
