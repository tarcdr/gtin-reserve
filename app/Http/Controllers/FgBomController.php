<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackMaterialCreateRequest;
use App\Http\Requests\PackMaterialDeleteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class FgBomController extends PackMaterialController
{
  public function new(Request $request): Response
  {
    $request->merge(['ownerLevel' => 'fg']);

    return parent::new($request);
  }

  public function save(PackMaterialCreateRequest $request): RedirectResponse
  {
    $componentId = $request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    );

    $this->saveFgComponentBom(array_merge($request->all(), [
      'ownerLevel' => 'fg',
      'componentId' => $componentId,
    ]), $request->user()?->user_login, $request->user()?->role);

    return Redirect::route($request->get('backRoute', 'product.view'), [
      'materialId' => $request->get('backMaterialId')
        ?: $request->get('referentMaterialId')
        ?: $request->get('materialId')
        ?: $request->get('fgMaterialId'),
    ]);
  }

  public function delete(PackMaterialDeleteRequest $request): RedirectResponse
  {
    $debug = $this->deleteFgComponentBom(array_merge($request->validated(), [
      'ownerLevel' => 'fg',
    ]), $request->user()?->user_login, $request->user()?->role);

    return Redirect::route($request->get('backRoute', 'product.view'), [
      'materialId' => $request->get('backMaterialId')
        ?: $request->get('referentMaterialId')
        ?: $request->get('materialId')
        ?: $request->get('fgMaterialId'),
    ])->with('message', 'Delete procedure executed.')
      ->with('deleteDebug', $debug);
  }
}
