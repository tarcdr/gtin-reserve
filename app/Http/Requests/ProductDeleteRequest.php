<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductDeleteRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'materialId' => ['required'],
      'fgMaterialId' => ['nullable'],
      'bomId' => ['nullable'],
      'fgBomId' => ['nullable'],
      'brand' => ['nullable'],
      'mattype' => ['nullable'],
      'subMattype' => ['nullable'],
    ];
  }

  public function attributes(): array
  {
    return [
      'materialId' => 'Material ID FG',
      'bomId' => 'FG BOM ID',
      'fgBomId' => 'FG BOM ID',
    ];
  }
}
