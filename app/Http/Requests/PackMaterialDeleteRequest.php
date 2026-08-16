<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackMaterialDeleteRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'ownerLevel' => ['nullable', 'in:fg,semiFgLv1,semiFgLv2,businessSupply'],
      'actionMode' => ['nullable'],
      'deleteScope' => ['nullable'],
      'backRoute' => ['nullable'],
      'backMaterialId' => ['nullable'],
      'referentMaterialId' => ['nullable'],
      'materialId' => ['nullable'],
      'fgMaterialId' => ['nullable'],
      'fgBomId' => ['nullable'],
      'bomId' => ['nullable'],
      'semiFgLvBomId' => ['nullable'],
      'semiFgLv1BomId' => ['nullable'],
      'semiFgLv2BomId' => ['nullable'],
      'levelMaterialId' => ['nullable'],
      'componentId' => ['nullable'],
      'materialIdM4' => ['nullable'],
      'materialIdM5' => ['nullable'],
      'fgDetail' => ['nullable', 'array'],
      'ownerDetail' => ['nullable', 'array'],
      'components' => ['nullable', 'array'],
    ];
  }

  public function attributes(): array
  {
    return [
      'componentId' => 'Component ID',
      'bomId' => 'BOM ID',
      'semiFgLvBomId' => 'Semi FG BOM ID',
      'fgBomId' => 'FG BOM ID',
      'materialId' => 'Material ID',
      'fgMaterialId' => 'FG Material ID',
      'levelMaterialId' => 'Level Material ID',
    ];
  }
}
