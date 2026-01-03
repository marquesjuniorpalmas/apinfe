<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmitenteRequest extends FormRequest
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
            'estado' => 'required|array',
            'estado.nome' => 'required|string|max:100',
            'estado.codigo_ibge' => 'required|string|max:10',
            'estado.uf' => 'required|string|size:2',
            'estado.regiao' => 'required|integer|min:1|max:5',
            'estado.perc_aliq_icms_interna' => 'required|numeric|min:0|max:100',

            'cidade' => 'required|array',
            'cidade.nome' => 'required|string|max:100',
            'cidade.codigo_ibge' => 'required|string|max:20',

            'emitente' => 'required|array',
            'emitente.razao_social' => 'required|string|max:180',
            'emitente.fantasia' => 'nullable|string|max:130',
            'emitente.cnpj' => 'required|string|size:14|unique:emitentes,cnpj',
            'emitente.token_ibpt' => 'nullable|string',
            'emitente.codigo_csc_id' => 'nullable|integer',
            'emitente.codigo_csc' => 'nullable|string',
            'emitente.inscricao_estadual' => 'nullable|string|max:20',
            'emitente.inscricao_municipal' => 'nullable|string|max:20',
            'emitente.conteudo_logotipo' => 'nullable|string',
            'emitente.conteudo_certificado' => 'required|string',
            'emitente.caminho_certificado' => 'nullable|string',
            'emitente.senha_certificado' => 'required|string',
            'emitente.codigo_postal' => 'required|string|max:20',
            'emitente.logradouro' => 'required|string|max:150',
            'emitente.numero' => 'required|string|max:20',
            'emitente.bairro' => 'required|string|max:100',
            'emitente.complemento' => 'nullable|string|max:50',
            'emitente.telefone' => 'nullable|string|max:15',
            'emitente.email' => 'required|email|max:150',
            'emitente.regime_tributario' => 'required|integer',
            'emitente.aliquota_geral_simples' => 'nullable|numeric|min:0|max:100',
            'emitente.ambiente_fiscal' => 'required|integer|in:1,2',

            'user' => 'required|array',
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|max:255|unique:users,email',
            'user.password' => 'required|string|min:6',
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
            'estado.required' => 'Os dados do estado são obrigatórios',
            'estado.nome.required' => 'O nome do estado é obrigatório',
            'estado.codigo_ibge.required' => 'O código IBGE do estado é obrigatório',
            'estado.uf.required' => 'A UF do estado é obrigatória',
            'estado.uf.size' => 'A UF deve conter exatamente 2 caracteres',
            'estado.regiao.required' => 'A região do estado é obrigatória',
            'estado.perc_aliq_icms_interna.required' => 'A porcentagem de alíquota ICMS interna é obrigatória',

            'cidade.required' => 'Os dados da cidade são obrigatórios',
            'cidade.nome.required' => 'O nome da cidade é obrigatório',
            'cidade.codigo_ibge.required' => 'O código IBGE da cidade é obrigatório',

            'emitente.required' => 'Os dados do emitente são obrigatórios',
            'emitente.razao_social.required' => 'A razão social é obrigatória',
            'emitente.cnpj.required' => 'O CNPJ é obrigatório',
            'emitente.cnpj.size' => 'O CNPJ deve conter exatamente 14 caracteres',
            'emitente.cnpj.unique' => 'Este CNPJ já está cadastrado',
            'emitente.conteudo_certificado.required' => 'O conteúdo do certificado é obrigatório',
            'emitente.senha_certificado.required' => 'A senha do certificado é obrigatória',
            'emitente.codigo_postal.required' => 'O CEP é obrigatório',
            'emitente.logradouro.required' => 'O logradouro é obrigatório',
            'emitente.numero.required' => 'O número é obrigatório',
            'emitente.bairro.required' => 'O bairro é obrigatório',
            'emitente.email.required' => 'O email é obrigatório',
            'emitente.email.email' => 'O email informado é inválido',
            'emitente.regime_tributario.required' => 'O regime tributário é obrigatório',
            'emitente.ambiente_fiscal.required' => 'O ambiente fiscal é obrigatório',
            'emitente.ambiente_fiscal.in' => 'O ambiente fiscal deve ser 1 (Produção) ou 2 (Homologação)',

            'user.required' => 'Os dados do usuário são obrigatórios',
            'user.name.required' => 'O nome do usuário é obrigatório',
            'user.email.required' => 'O email do usuário é obrigatório',
            'user.email.email' => 'O email do usuário é inválido',
            'user.email.unique' => 'Este email já está cadastrado',
            'user.password.required' => 'A senha é obrigatória',
            'user.password.min' => 'A senha deve conter no mínimo 6 caracteres',
        ];
    }
}

