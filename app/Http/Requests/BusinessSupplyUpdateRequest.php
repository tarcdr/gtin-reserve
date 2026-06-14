<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplyUpdateRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'bomBsId' => ['required'],
      'searchDesc' => ['required'],
      'compDescEn' => ['required'],
      'compDescTh' => ['required'],
      'uom' => ['required'],
    ];
  }

  public function attributes(): array
  {
    return [
      'bomBsId' => 'BOM ID for Business Supply',
      'searchDesc' => 'Search Description',
      'compDescEn' => 'Full Description (EN)',
      'compDescTh' => 'Full Description (TH)',
      'uom' => 'UOM',
    ];
  }
}
