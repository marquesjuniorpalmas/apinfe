<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAmbienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ambiente_fiscal' => 'required|integer|in:1,2',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ambiente_fiscal.required' => 'O ambiente fiscal é obrigatório',
            'ambiente_fiscal.integer' => 'O ambiente fiscal deve ser um número inteiro',
            'ambiente_fiscal.in' => 'O ambiente fiscal deve ser 1 (Produção) ou 2 (Homologação)',
        ];
    }
}

