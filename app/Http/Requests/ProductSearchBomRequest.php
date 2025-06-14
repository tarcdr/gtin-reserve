<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSearchBomRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'brand'           => ['required'],
      'mattype'         => ['required'],
      'subMattype'      => ['required'],
      'materialId'      => ['required'],
      'materialId.code' => ['required'],
    ];
  }
  public function attributes(): array
  {
    return [
      'brand'           => 'Brand',
      'mattype'         => 'Mattye',
      'subMattype'      => 'Sub Mattype',
      'materialId'      => 'Material ID FG',
      'materialId.code' => 'Material ID FG (Code)',
    ];
  }
}
