<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmitenteRequest extends FormRequest
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
        $emitenteId = $this->route('id');
        
        return [
            'razao_social' => 'sometimes|string|max:180',
            'fantasia' => 'nullable|string|max:130',
            'cnpj' => [
                'sometimes',
                'string',
                'size:14',
                Rule::unique('emitentes', 'cnpj')->ignore($emitenteId)
            ],
            'token_ibpt' => 'nullable|string',
            'codigo_csc_id' => 'nullable|integer',
            'codigo_csc' => 'nullable|string',
            'inscricao_estadual' => 'nullable|string|max:20',
            'inscricao_municipal' => 'nullable|string|max:20',
            'conteudo_logotipo' => 'nullable|string',
            'codigo_postal' => 'sometimes|string|max:20',
            'logradouro' => 'sometimes|string|max:150',
            'numero' => 'sometimes|string|max:20',
            'bairro' => 'sometimes|string|max:100',
            'complemento' => 'nullable|string|max:50',
            'telefone' => 'nullable|string|max:15',
            'email' => 'sometimes|email|max:150',
            'regime_tributario' => 'sometimes|integer',
            'aliquota_geral_simples' => 'nullable|numeric|min:0|max:100',
            'cidade_id' => 'sometimes|exists:cidades,id',
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
            'razao_social.max' => 'A razão social não pode ter mais de 180 caracteres',
            'fantasia.max' => 'O nome fantasia não pode ter mais de 130 caracteres',
            'cnpj.size' => 'O CNPJ deve conter exatamente 14 caracteres',
            'cnpj.unique' => 'Este CNPJ já está cadastrado',
            'codigo_postal.max' => 'O CEP não pode ter mais de 20 caracteres',
            'logradouro.max' => 'O logradouro não pode ter mais de 150 caracteres',
            'numero.max' => 'O número não pode ter mais de 20 caracteres',
            'bairro.max' => 'O bairro não pode ter mais de 100 caracteres',
            'complemento.max' => 'O complemento não pode ter mais de 50 caracteres',
            'telefone.max' => 'O telefone não pode ter mais de 15 caracteres',
            'email.email' => 'O email informado é inválido',
            'email.max' => 'O email não pode ter mais de 150 caracteres',
            'aliquota_geral_simples.numeric' => 'A alíquota geral do simples deve ser um número',
            'aliquota_geral_simples.min' => 'A alíquota geral do simples não pode ser menor que 0',
            'aliquota_geral_simples.max' => 'A alíquota geral do simples não pode ser maior que 100',
            'cidade_id.exists' => 'A cidade informada não existe',
        ];
    }
}

