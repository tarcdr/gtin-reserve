<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'brand' => ['required'],
            'mattype' => ['required'],
            'gtinPcsCode'    => ['required_if:gtinExistPcs,true'],
            'gtinPackCode'   => ['required_if:gtinExistPack,true'],
            'gtinPcsChoose'  => ['required_with_all:brand,mattype,material_id'],
            'gtinPackChoose' => ['required_with_all:brand,mattype,material_id'],
        ];
    }
}
