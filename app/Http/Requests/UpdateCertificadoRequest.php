<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificadoRequest extends FormRequest
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
            'conteudo_certificado' => 'required_without_all:caminho_certificado|string',
            'caminho_certificado' => 'required_without_all:conteudo_certificado|string',
            'senha_certificado' => 'required|string',
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
            'conteudo_certificado.required_without_all' => 'É necessário informar o conteúdo do certificado ou o caminho do certificado',
            'caminho_certificado.required_without_all' => 'É necessário informar o conteúdo do certificado ou o caminho do certificado',
            'senha_certificado.required' => 'A senha do certificado é obrigatória',
        ];
    }
}

