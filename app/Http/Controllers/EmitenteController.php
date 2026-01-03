<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmitenteRequest;
use App\Http\Requests\UpdateAmbienteRequest;
use App\Http\Requests\UpdateCertificadoRequest;
use App\Http\Requests\UpdateEmitenteRequest;
use App\Models\Cidade;
use App\Models\Emitente;
use App\Models\Estado;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class EmitenteController extends Controller
{
    use ApiResponser;

    /**
     * Cadastra um novo emitente com estado, cidade e usuário
     *
     * @param StoreEmitenteRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmitenteRequest $request): JsonResponse
    {
        $dados = $request->validated();

        DB::beginTransaction();

        try {
            // Verificar ou criar estado
            $estado = Estado::firstOrCreate(
                ['codigo_ibge' => $dados['estado']['codigo_ibge']],
                [
                    'nome' => $dados['estado']['nome'],
                    'uf' => strtoupper($dados['estado']['uf']),
                    'regiao' => $dados['estado']['regiao'],
                    'perc_aliq_icms_interna' => $dados['estado']['perc_aliq_icms_interna'],
                ]
            );

            // Verificar ou criar cidade
            $cidade = Cidade::firstOrCreate(
                [
                    'estado_id' => $estado->id,
                    'codigo_ibge' => $dados['cidade']['codigo_ibge']
                ],
                [
                    'nome' => $dados['cidade']['nome'],
                ]
            );

            // Criar emitente
            $emitente = Emitente::create([
                'cidade_id' => $cidade->id,
                'razao_social' => $dados['emitente']['razao_social'],
                'fantasia' => $dados['emitente']['fantasia'] ?? null,
                'cnpj' => $dados['emitente']['cnpj'],
                'token_ibpt' => $dados['emitente']['token_ibpt'] ?? null,
                'codigo_csc_id' => $dados['emitente']['codigo_csc_id'] ?? 1,
                'codigo_csc' => $dados['emitente']['codigo_csc'] ?? '',
                'inscricao_estadual' => $dados['emitente']['inscricao_estadual'] ?? null,
                'inscricao_municipal' => $dados['emitente']['inscricao_municipal'] ?? null,
                'conteudo_logotipo' => $dados['emitente']['conteudo_logotipo'] ?? null,
                'conteudo_certificado' => $dados['emitente']['conteudo_certificado'],
                'caminho_certificado' => $dados['emitente']['caminho_certificado'] ?? null,
                'senha_certificado' => encrypt($dados['emitente']['senha_certificado']),
                'codigo_postal' => $dados['emitente']['codigo_postal'],
                'logradouro' => $dados['emitente']['logradouro'],
                'numero' => $dados['emitente']['numero'],
                'bairro' => $dados['emitente']['bairro'],
                'complemento' => $dados['emitente']['complemento'] ?? null,
                'telefone' => $dados['emitente']['telefone'] ?? null,
                'email' => $dados['emitente']['email'],
                'regime_tributario' => $dados['emitente']['regime_tributario'],
                'aliquota_geral_simples' => $dados['emitente']['aliquota_geral_simples'] ?? null,
                'ambiente_fiscal' => $dados['emitente']['ambiente_fiscal'],
            ]);

            // Criar usuário vinculado ao emitente
            $user = User::create([
                'emitente_id' => $emitente->id,
                'name' => $dados['user']['name'],
                'email' => $dados['user']['email'],
                'password' => Hash::make($dados['user']['password']),
                'email_verified_at' => now(),
            ]);

            DB::commit();

            return $this->success([
                'message' => 'Emitente cadastrado com sucesso!',
                'emitente' => [
                    'id' => $emitente->id,
                    'razao_social' => $emitente->razao_social,
                    'cnpj' => $emitente->cnpj,
                    'email' => $emitente->email,
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log do erro para debug
            \Log::error('Erro ao cadastrar emitente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'codigo_ibge_cidade' => $dados['cidade']['codigo_ibge'] ?? null,
            ]);
            
            // Verifica se o erro é relacionado a cidade/IBGE
            $errorMessage = $e->getMessage();
            if (stripos($errorMessage, 'cidade') !== false || stripos($errorMessage, 'IBGE') !== false) {
                return $this->error([
                    'error_message' => $errorMessage,
                    'codigo_ibge_cidade' => $dados['cidade']['codigo_ibge'] ?? null,
                    'sugestao' => 'Verifique se o código IBGE da cidade está correto. O sistema cria automaticamente a cidade se ela não existir.',
                ], Response::HTTP_BAD_REQUEST);
            }
            
            return $this->error([
                'error_message' => 'Erro ao cadastrar emitente',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exibe os dados do emitente autenticado
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->emitente) {
                return $this->error([
                    'error_message' => 'Emitente não encontrado',
                ], Response::HTTP_NOT_FOUND);
            }

            $emitente = $user->emitente->load('cidade.estado');

            return $this->success([
                'emitente' => [
                    'id' => $emitente->id,
                    'razao_social' => $emitente->razao_social,
                    'fantasia' => $emitente->fantasia,
                    'cnpj' => $emitente->cnpj,
                    'token_ibpt' => $emitente->token_ibpt,
                    'codigo_csc_id' => $emitente->codigo_csc_id,
                    'codigo_csc' => $emitente->codigo_csc,
                    'inscricao_estadual' => $emitente->inscricao_estadual,
                    'inscricao_municipal' => $emitente->inscricao_municipal,
                    'codigo_postal' => $emitente->codigo_postal,
                    'logradouro' => $emitente->logradouro,
                    'numero' => $emitente->numero,
                    'bairro' => $emitente->bairro,
                    'complemento' => $emitente->complemento,
                    'telefone' => $emitente->telefone,
                    'email' => $emitente->email,
                    'regime_tributario' => $emitente->regime_tributario,
                    'aliquota_geral_simples' => $emitente->aliquota_geral_simples,
                    'ambiente_fiscal' => $emitente->ambiente_fiscal,
                    'ambiente_fiscal_descricao' => $emitente->ambiente_fiscal == 1 ? 'Produção' : 'Homologação',
                    'caminho_certificado' => $emitente->caminho_certificado,
                    'tem_certificado' => !empty($emitente->conteudo_certificado) || !empty($emitente->caminho_certificado),
                    'cidade' => [
                        'id' => $emitente->cidade->id,
                        'nome' => $emitente->cidade->nome,
                        'codigo_ibge' => $emitente->cidade->codigo_ibge,
                        'estado' => [
                            'id' => $emitente->cidade->estado->id,
                            'nome' => $emitente->cidade->estado->nome,
                            'uf' => $emitente->cidade->estado->uf,
                            'codigo_ibge' => $emitente->cidade->estado->codigo_ibge,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => 'Erro ao buscar dados do emitente',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualiza os dados do emitente
     *
     * @param UpdateEmitenteRequest $request
     * @return JsonResponse
     */
    public function update(UpdateEmitenteRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->emitente) {
                return $this->error([
                    'error_message' => 'Emitente não encontrado',
                ], Response::HTTP_NOT_FOUND);
            }

            $emitente = $user->emitente;
            $dados = $request->validated();

            // Atualizar apenas os campos fornecidos
            $emitente->fill($dados);
            $emitente->save();

            return $this->success([
                'message' => 'Emitente atualizado com sucesso!',
                'emitente' => [
                    'id' => $emitente->id,
                    'razao_social' => $emitente->razao_social,
                    'cnpj' => $emitente->cnpj,
                    'email' => $emitente->email,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => 'Erro ao atualizar emitente',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualiza o certificado digital do emitente
     *
     * @param UpdateCertificadoRequest $request
     * @return JsonResponse
     */
    public function updateCertificado(UpdateCertificadoRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->emitente) {
                return $this->error([
                    'error_message' => 'Emitente não encontrado',
                ], Response::HTTP_NOT_FOUND);
            }

            $emitente = $user->emitente;
            $dados = $request->validated();

            // Atualizar certificado
            if (isset($dados['conteudo_certificado'])) {
                $emitente->conteudo_certificado = $dados['conteudo_certificado'];
            }
            
            if (isset($dados['caminho_certificado'])) {
                $emitente->caminho_certificado = $dados['caminho_certificado'];
            }
            
            $emitente->senha_certificado = encrypt($dados['senha_certificado']);
            $emitente->save();

            return $this->success([
                'message' => 'Certificado atualizado com sucesso!',
            ]);
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => 'Erro ao atualizar certificado',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualiza o ambiente fiscal (Produção ou Homologação)
     *
     * @param UpdateAmbienteRequest $request
     * @return JsonResponse
     */
    public function updateAmbiente(UpdateAmbienteRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->emitente) {
                return $this->error([
                    'error_message' => 'Emitente não encontrado',
                ], Response::HTTP_NOT_FOUND);
            }

            $emitente = $user->emitente;
            $dados = $request->validated();

            $emitente->ambiente_fiscal = $dados['ambiente_fiscal'];
            $emitente->save();

            $ambienteDescricao = $dados['ambiente_fiscal'] == 1 ? 'Produção' : 'Homologação';

            return $this->success([
                'message' => "Ambiente fiscal alterado para {$ambienteDescricao} com sucesso!",
                'ambiente_fiscal' => $emitente->ambiente_fiscal,
                'ambiente_fiscal_descricao' => $ambienteDescricao,
            ]);
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => 'Erro ao atualizar ambiente fiscal',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
