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
      'bomBsDesc' => ['nullable'],
      'bsId' => ['required'],
      'underType' => ['required'],
      'matType' => ['required'],
      'subMatType' => ['required'],
      'brand' => ['nullable'],
      'fgMaterialId' => ['nullable'],
      'site' => ['required'],
      'searchDesc' => ['required'],
      'compDescEn' => ['required'],
      'compDescTh' => ['required'],
      'uom' => ['required'],
      'productCat' => ['nullable'],
      'prodSubCat' => ['nullable'],
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
      'brand' => 'Brand',
      'fgMaterialId' => 'Material ID FG',
      'site' => 'Site',
      'searchDesc' => 'Search Description',
      'compDescEn' => 'Full Description (EN)',
      'compDescTh' => 'Full Description (TH)',
      'uom' => 'UOM',
      'productCat' => 'Product Category',
      'prodSubCat' => 'Product Sub Category',
    ];
  }
}
