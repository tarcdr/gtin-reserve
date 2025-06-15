<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackMaterialCreateRequest extends FormRequest
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
      'subMattype'   => ['required'],
      'componentId'  => ['required'],
      'searchDesc'   => ['required'],
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
      'subMattype'   => 'Sub Mattype',
      'componentId'  => 'Component ID',
      'searchDesc'   => 'Search Description',
      'fullDescEn'   => 'Full Description (EN)',
      'fullDescTh'   => 'Full Description (TH)',
      'uom'          => 'UOM',
    ];
  }
}
