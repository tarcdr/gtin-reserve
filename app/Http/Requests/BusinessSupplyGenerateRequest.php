<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplyGenerateRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'site' => ['required'],
      'underType' => ['required', 'in:FG,BRAND,NOT ALL'],
      'fgMaterialId' => ['required_if:underType,FG'],
      'brand' => ['required_if:underType,BRAND'],
      'matType' => ['required'],
      'subMatType' => ['required'],
    ];
  }

  public function attributes(): array
  {
    return [
      'underType' => 'Business Supply Under Type',
      'fgMaterialId' => 'Material ID FG',
      'brand' => 'Brand',
      'site' => 'Site',
      'matType' => 'Mattype',
      'subMatType' => 'Sub Mattype',
    ];
  }
}
