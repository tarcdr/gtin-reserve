<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplyComponentSaveRequest extends FormRequest
{
  protected function prepareForValidation(): void
  {
    if (!$this->filled('bomId') && $this->filled('bomBsId')) {
      $this->merge([
        'bomId' => $this->input('bomBsId'),
      ]);
    }

    if (!$this->filled('matType') && $this->filled('mattype')) {
      $this->merge([
        'matType' => $this->input('mattype'),
      ]);
    }

    if (!$this->filled('subMatType') && $this->filled('subMattype')) {
      $this->merge([
        'subMatType' => $this->input('subMattype'),
      ]);
    }
  }

  public function rules(): array
  {
    return [
      'bomId' => ['required'],
      'matType' => ['required'],
      'subMatType' => ['required'],
      'productCat' => ['required'],
      'productSubCat' => ['required'],
      'componentId' => ['required'],
      'searchDesc' => ['required'],
      'fullDescEn' => ['required'],
      'fullDescTh' => ['required'],
      'uom' => ['required'],
      'site' => ['required'],
      'bizsupId' => ['nullable'],
    ];
  }

  public function attributes(): array
  {
    return [
      'bomId' => 'Business Supply BOM ID',
      'matType' => 'Mattype',
      'subMatType' => 'Sub Mattype',
      'productCat' => 'Product Category',
      'productSubCat' => 'Product Sub Category',
      'componentId' => 'Component ID',
      'searchDesc' => 'Search Description',
      'fullDescEn' => 'Full Description (EN)',
      'fullDescTh' => 'Full Description (TH)',
      'uom' => 'UOM',
      'site' => 'Site',
    ];
  }
}
