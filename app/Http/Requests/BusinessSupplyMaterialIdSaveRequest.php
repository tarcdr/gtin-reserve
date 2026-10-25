<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSupplyMaterialIdSaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bomBsId' => ['required'],
            'matType' => ['required', 'string', 'in:1,5'],
            'subMatType' => ['required'],
            'componentId' => ['required'],
            'materialId' => ['nullable'],
            'searchDesc' => ['nullable'],
            'fullDescEn' => ['nullable'],
            'fullDescTh' => ['nullable'],
            'uom' => ['nullable'],
            'site' => ['nullable'],
            'bizsupId' => ['nullable'],
            'brand' => ['required_if:matType,1', 'nullable', 'string'],
            'actionMode' => ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bomBsId' => 'Business Supply BOM ID',
            'matType' => 'Mattype',
            'subMatType' => 'Sub Mattype',
            'componentId' => 'Component BD Material ID',
        ];
    }
}
