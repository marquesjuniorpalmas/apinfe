<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {


        DB::table('estados')->insert([
            'nome' => 'Sao Paulo',
            'codigo_ibge' => '35',
            'uf' => 'SP',
            'regiao' => 4,
            'perc_aliq_icms_interna' => 18.00,
        ]);

        DB::table('cidades')->insert([
            'estado_id' => 1,
            'nome' => 'Sao Paulo',
            'codigo_ibge' => '3550308'
        ]);

        $conteudo_certificado = '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';

        DB::table('emitentes')->insert([
            'cidade_id' => 1,
            'razao_social' => 'ANDREIA DIAS SILVA DE OLIVEIRA',
            'fantasia' => 'Futura Shop',
            'cnpj' => '41925973000119',
            'token_ibpt' => null,
            'codigo_csc_id' => 1,
            'codigo_csc' => '',
            'inscricao_estadual' => '131179839119',
            'inscricao_municipal' => '69379742',
            'conteudo_certificado' => $conteudo_certificado,
            'caminho_certificado' => null,
            'senha_certificado' => encrypt('1234'),
            'codigo_postal' => '02118010',
            'logradouro' => 'RUA TOMAS SPEERS',
            'numero' => '824',
            'bairro' => 'VILA MARIA',
            'complemento' => 'SALA 2',
            'telefone' => '63992415490',
            'email' => 'junioralphasistemas@gmail.com',
            'regime_tributario' => 1,
            'aliquota_geral_simples' => null,
            'ambiente_fiscal' => 2,
        ]);

        DB::table('users')->insert([
            'emitente_id' => 1,
            'name' => 'Lourival Marques',
            'email' => 'junioralphasistemas@gmail.com',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'password' => bcrypt('123456'),
        ]);
    }
}
