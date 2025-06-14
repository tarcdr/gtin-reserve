<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductCreateRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'brand'        => ['required'],
      'mattype'      => ['required'],
      'site'         => ['required_if:mattype,1'],
      'subMattype'   => ['required'],
      'productGroup' => ['required'],
      'finishGoods'  => ['required'],
      'materialDesc' => ['required'],
      'fullDescEn'   => ['required'],
      'fullDescTh'   => ['required'],
      'uom'          => ['required'],
    ];
  }

  public function attributes(): array
  {
    return [
      'brand'        => 'Brand',
      'mattype'      => 'Mattye',
      'site'         => 'Site',
      'subMattype'   => 'Sub Mattype',
      'productGroup' => 'Product Group',
      'finishGoods'  => 'Finish Goods',
      'materialDesc' => 'Search Description',
      'fullDescEn'   => 'Full Description (EN)',
      'fullDescTh'   => 'Full Description (TH)',
      'uom'          => 'UOM',
    ];
  }
}
