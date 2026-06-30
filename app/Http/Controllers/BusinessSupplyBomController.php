<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackMaterialCreateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class BusinessSupplyBomController extends PackMaterialController
{
  public function new(Request $request): Response
  {
    $request->merge(['ownerLevel' => 'businessSupply']);

    return parent::new($request);
  }

  public function callNew(Request $request): RedirectResponse
  {
    return Redirect::route('packmaterial.new', [
      'ownerLevel' => 'businessSupply',
      'actionMode' => $request->get('actionMode', 'create'),
      'backRoute' => $request->get('backRoute'),
      'referentMaterialId' => $request->get('referentMaterialId'),
      'materialId' => $request->get('materialId'),
      'fgMaterialId' => $request->get('fgMaterialId'),
      'componentId' => $request->get('componentId'),
      'bomId' => $request->get('bomId'),
      'bomDesc' => $request->get('bomDesc'),
      'subMattype' => $request->get('subMattype'),
    ]);
  }

  public function save(PackMaterialCreateRequest $request): RedirectResponse
  {
    $componentId = $request->componentId ?: sprintf(
      '56%s%s',
      $request->productCat ?: '00',
      $request->productSubCat ?: '00'
    );

    $componentItem = [
      'code' => $componentId,
      'label' => $request->searchDesc ?: $request->fullDescEn ?: $componentId,
      'status' => 'INS',
      'searchDesc' => $request->searchDesc,
      'fullDescEn' => $request->fullDescEn,
      'fullDescTh' => $request->fullDescTh,
      'uom' => $request->uom,
      'productCat' => $request->productCat,
      'productSubCat' => $request->productSubCat,
    ];

    $request->merge([
      'ownerLevel' => 'businessSupply',
    ]);

    return $this->redirectToOwner($request, $this->upsertComponent($request->get('components', []), $componentItem));
  }
}
