<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplySaveRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'bomBsId' => ['required'],
      'matType' => ['required'],
      'subMatType' => ['required'],
      'productCat' => ['required'],
      'prodSubCat' => ['nullable'],
      'componentId' => ['required'],
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
      'matType' => 'Mattype',
      'subMatType' => 'Sub Mattype',
      'productCat' => 'Product Category',
      'prodSubCat' => 'Product Sub Category',
      'componentId' => 'Business Supply ID',
      'searchDesc' => 'Search Description',
      'compDescEn' => 'Full Description (EN)',
      'compDescTh' => 'Full Description (TH)',
      'uom' => 'UOM',
    ];
  }
}
