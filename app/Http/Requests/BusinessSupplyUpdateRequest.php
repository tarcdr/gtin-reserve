<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplyUpdateRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'bomBsId' => ['required'],
      'bomBsDesc' => ['nullable'],
      'bsId' => ['required'],
      'underType' => ['required'],
      'matType' => ['required'],
      'subMatType' => ['required'],
      'fgMaterialId' => ['nullable'],
      'brand' => ['nullable'],
      'site' => ['required'],
      'searchDesc' => ['required'],
      'compDescEn' => ['required'],
      'compDescTh' => ['required'],
      'uom' => ['required'],
    ];
  }

  public function attributes(): array
  {
    return [
      'bomBsId' => 'Business Supply BOM ID',
      'bomBsDesc' => 'Business Supply BOM Description',
      'bsId' => 'Business Supply ID',
      'underType' => 'Business Supply Type',
      'matType' => 'Mattype',
      'subMatType' => 'Sub Mattype',
      'fgMaterialId' => 'Material ID FG',
      'brand' => 'Brand',
      'site' => 'Site',
      'searchDesc' => 'Search Description',
      'compDescEn' => 'Full Description (EN)',
      'compDescTh' => 'Full Description (TH)',
      'uom' => 'UOM',
    ];
  }
}
