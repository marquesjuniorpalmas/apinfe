<?php

declare(strict_types=1);

namespace App\Services\dfe;

use App\Models\Documento;
use App\Models\Emitente;
use App\Models\Evento;
use Carbon\Carbon;
use Exception;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\DA\NFe\Daevento;
use stdClass;


/**
 * Class DocumentosFiscaisAbstract
 * Essa classe não pode ser instanciada. Deve ser herdada.
 */
abstract class DocumentosFiscaisAbstract implements DocumentosFiscaisInterface
{
    protected $configJson;
    protected $tools;
    protected $nfe;
    protected $modelo;
    protected $documentoId;
    protected $chave;
    protected $emitente;


    public function __construct(Emitente $emitente, string $modelo)
    {

        try {

            if(!($modelo === '55') && !($modelo === '65')) {
                throw new Exception('Somente os modelos 55 e 65 são permitidos', 9001);
            }

            $this->emitente = $emitente;

            $config = [

                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => $this->emitente->ambiente_fiscal,
                "razaosocial" => $this->emitente->razao_social,
                "siglaUF" => $this->emitente->cidade->estado->uf,
                "cnpj" => $this->emitente->cnpj,
                "schemes" => "PL_008i2",
                "versao" => "4.00",
                "tokenIBPT" => $this->emitente->token_ibpt,
                "CSC" => $this->emitente->codigo_csc,
                "CSCid" => (string) $this->emitente->codigo_csc_id,
                "aProxyConf" => [
                    "proxyIp" => "",
                    "proxyPort" => "",
                    "proxyUser" => "",
                    "proxyPass" => ""
                ]
            ];

            $this->modelo = $modelo;

            $this->configJson = json_encode($config);

            // Verificar se o certificado existe
            if (empty($this->emitente->conteudo_certificado) && empty($this->emitente->caminho_certificado)) {
                throw new Exception('Certificado digital não configurado para o emitente', 9001);
            }

            // Tentar ler o certificado
            try {
                $certificadoConteudo = !empty($this->emitente->conteudo_certificado) 
                    ? base64_decode($this->emitente->conteudo_certificado) 
                    : file_get_contents($this->emitente->caminho_certificado);
                
                $senhaCertificado = decrypt($this->emitente->senha_certificado);
                
                $certificate = Certificate::readPfx($certificadoConteudo, $senhaCertificado);
            } catch (Exception $certError) {
                throw new Exception('Erro ao ler o certificado digital: ' . $certError->getMessage() . '. Verifique se o certificado e a senha estão corretos.', 9002);
            }

            // Inicializar Tools e Make
            try {
                $this->tools = new Tools($this->configJson, $certificate);
                $this->nfe = new Make();
            } catch (Exception $initError) {
                throw new Exception('Erro ao inicializar ferramentas de NFe: ' . $initError->getMessage(), 9003);
            }

            // Verificar se a inicialização foi bem-sucedida
            if (!$this->nfe) {
                throw new Exception('Falha ao inicializar o objeto Make para geração da NFe', 9004);
            }

            if (!$this->tools) {
                throw new Exception('Falha ao inicializar o objeto Tools para comunicação com a SEFAZ', 9005);
            }

        } catch (Exception $e) {
            // Limpar propriedades em caso de erro
            $this->nfe = null;
            $this->tools = null;
            
            // Lançar exceção para que o erro seja tratado adequadamente
            throw new Exception('Erro ao inicializar serviço de NFe: ' . $e->getMessage(), $e->getCode() ?: 9000);
        }
    }


    abstract public function buildNFeXml(Request $request);


    public function assignXml(array $data)
    {
        if(!is_null($data)) {
            if(!is_null($data['data'])) {
                $xmlsigned = $this->tools->signNFe($data['data']);

                $documentoData = [
                    'chave' => $data['chave'],
                    'numero' => $data['numero'],
                    'serie' => $data['serie'],
                    'conteudo_xml_assinado' => base64_encode($xmlsigned)
                ];

                $documento = $this->emitente->documentos()->create($documentoData);

                if($documento) {
                    return $documento;
                }
            }
        }
    }


    public function sendBatch(Documento $documento)
    {
        try {
            $idBatch = str_pad('100', 15, '0', STR_PAD_LEFT);

            $response = $this->tools->sefazEnviaLote([base64_decode($documento->conteudo_xml_assinado)], $idBatch, 1);

            $std = new Standardize();
            $stdClass = $std->toStd($response);
            print_r($stdClass);

            // Verificar se a resposta foi bem-sucedida
            if (!isset($stdClass->cStat)) {
                throw new Exception('Resposta inválida da SEFAZ ao enviar lote', 9006);
            }

            // Preparar dados do evento
            $eventoData = [
                'nome_evento' => 'envio_lote',
                'codigo' => $stdClass->cStat,
                'mensagem_retorno' => $stdClass->xMotivo ?? 'Sem mensagem',
                'data_hora_evento' => isset($stdClass->dhRecbto) ? Carbon::parse($stdClass->dhRecbto) : now(),
                'recibo' => null, // Será preenchido se existir
            ];

            // Verificar se há recibo (infRec só existe em respostas de sucesso com código 103 ou 105)
            if (isset($stdClass->infRec) && isset($stdClass->infRec->nRec)) {
                $eventoData['recibo'] = $stdClass->infRec->nRec;
            }

            // Código 104 = Lote processado (resposta síncrona quando há apenas 1 NFe no lote)
            // Neste caso, a resposta já contém protNFe->infProt com o status da NFe
            if ($stdClass->cStat == 104 && isset($stdClass->protNFe) && isset($stdClass->protNFe->infProt)) {
                // Armazenar o XML da resposta para processamento posterior
                $eventoData['resposta_xml'] = $response; // XML completo da resposta
                $evento = $documento->eventos()->create($eventoData);
                
                // Adicionar propriedades ao objeto para processamento
                $evento->tem_resposta_completa = true;
                $evento->protNFe = $stdClass->protNFe;
                $evento->resposta_xml = $response;
                
                return $evento;
            }

            // Verificar se o lote foi aceito (cStat 103, 104 ou 105)
            if ($stdClass->cStat == 103 || $stdClass->cStat == 104 || $stdClass->cStat == 105) {
                $evento = $documento->eventos()->create($eventoData);
            } else {
                // Lote rejeitado ou com erro
                $evento = json_decode(json_encode($eventoData));
                
                // Log do erro para debug
                \Log::warning('Lote rejeitado pela SEFAZ', [
                    'codigo' => $stdClass->cStat,
                    'mensagem' => $stdClass->xMotivo ?? 'Sem mensagem',
                    'documento_id' => $documento->id,
                ]);
            }
            
            return $evento;
            
        } catch (Exception $e) {
            \Log::error('Erro ao enviar lote para SEFAZ', [
                'message' => $e->getMessage(),
                'documento_id' => $documento->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception('Erro ao enviar lote para SEFAZ: ' . $e->getMessage(), $e->getCode() ?: 9007);
        }
    }


    public function getStatus(Evento $evento)
    {
        // Verificar se o evento tem recibo
        if (empty($evento->recibo)) {
            \Log::warning('Tentativa de consultar status sem recibo', [
                'evento_id' => $evento->id,
                'codigo' => $evento->codigo,
                'mensagem' => $evento->mensagem_retorno,
            ]);
            
            return [
                'sucesso' => false,
                'codigo' => $evento->codigo ?? 9998,
                'mensagem' => $evento->mensagem_retorno ?? 'Lote não foi aceito pela SEFAZ. Não há recibo para consultar.',
                'data' => []
            ];
        }

        try {
            $protocolo = $this->tools->sefazConsultaRecibo($evento->recibo);

            if (empty($protocolo)) {
                throw new Exception('Resposta vazia da SEFAZ ao consultar recibo', 9008);
            }

            $st = new Standardize();
            $stdClass = $st->toStd($protocolo);

            // Verificar se a estrutura da resposta está correta
            if (!isset($stdClass->protNFe) || !isset($stdClass->protNFe->infProt)) {
                throw new Exception('Resposta inválida da SEFAZ ao consultar recibo', 9009);
            }

            $cStat = $stdClass->protNFe->infProt->cStat;
            $xMotivo = $stdClass->protNFe->infProt->xMotivo ?? 'Sem mensagem';

            // Se o status não for 100 (autorizado), retornar erro
            if ($cStat != 100) {
                return [
                    'sucesso' => false,
                    'codigo' => $cStat,
                    'mensagem' => $xMotivo,
                    'data' => []
                ];
            }

            // Status 100 - Documento autorizado
            DB::beginTransaction();

            try {
                $documento = Documento::find($evento->documento_id);

                if (!$documento) {
                    throw new Exception('Documento não encontrado', 9010);
                }

                $documento->update([
                    'status' => 'autorizado',
                    'protocolo' => $stdClass->protNFe->infProt->nProt ?? null,
                ]);

                $documento = Documento::find($documento->id);

                // Preparar data_hora_evento com tratamento de erro
                $dataHoraEvento = now();
                if (isset($stdClass->protNFe->infProt->dhRecbto)) {
                    try {
                        $dataHoraEvento = Carbon::createFromFormat('c', $stdClass->protNFe->infProt->dhRecbto);
                    } catch (\Exception $dateException) {
                        \Log::warning('Erro ao parsear data do protocolo', [
                            'dhRecbto' => $stdClass->protNFe->infProt->dhRecbto ?? null,
                            'erro' => $dateException->getMessage()
                        ]);
                    }
                }

                $documento->eventos()->create([
                    'nome_evento' => 'consulta_status_documento',
                    'codigo' => $cStat,
                    'mensagem_retorno' => $xMotivo,
                    'data_hora_evento' => $dataHoraEvento,
                    'recibo' => null,
                ]);

                DB::commit();

                return [
                    'sucesso' => true,
                    'codigo' => 100,
                    'mensagem' => 'Documento fiscal autorizado com sucesso.',
                    'data' => $protocolo
                ];

            } catch (Exception $e) {
                DB::rollBack();
                \Log::error('Erro ao atualizar documento após autorização', [
                    'message' => $e->getMessage(),
                    'evento_id' => $evento->id,
                    'documento_id' => $evento->documento_id ?? null,
                ]);
                
                return [
                    'sucesso' => false,
                    'codigo' => $e->getCode() ?: 9011,
                    'mensagem' => 'Falha ao consultar o status do documento',
                    'correcao' => 'Entre em contato com o suporte',
                    'data' => null
                ];
            }

        } catch (Exception $e) {
            \Log::error('Erro ao consultar status do recibo na SEFAZ', [
                'message' => $e->getMessage(),
                'evento_id' => $evento->id,
                'recibo' => $evento->recibo,
            ]);
            
            return [
                'sucesso' => false,
                'codigo' => $e->getCode() ?: 9012,
                'mensagem' => 'Erro ao consultar status na SEFAZ: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }


    public function addProtocolIntoXml(Documento $documento, $protocolo)
    {
        if(is_null($documento->conteudo_xml_assinado) || empty($documento->conteudo_xml_assinado)){
            return [
                'sucesso' => false,
                'codigo' => 9000,
                'mensagem' => 'Erro ao autorizar o documento. Entre em contato com o suporte.',
                'data' => []
            ];
        }

        $authorizedXml = Complements::toAuthorize(base64_decode($documento->conteudo_xml_assinado), $protocolo);

        DB::beginTransaction();
        try {

            $documento->update([
                'conteudo_xml_autorizado' => base64_encode($authorizedXml),
                'conteudo_xml_assinado' => ''
            ]);
            DB::commit();

            $documento = Documento::find($documento->id);

            return [
                'sucesso' => true,
                'codigo' => 6000,
                'mensagem' => 'Documento fiscal autorizado com sucesso.',
                'data' => $documento
            ];

        } catch(Exception $e) {
            DB::rollBack();

            return [
                'sucesso' => false,
                'codigo' => 9000,
                'mensagem' => 'Erro ao autorizar o documento. Entre em contato com o suporte.',
                'data' => []
            ];
        }

    }



    public function cancelDocument($request)
    {
        try {
            $this->tools->model(55);

            $chave = $request['chave'];
            $xJust = $request['justificativa'];
            $nProt = $request['num_prot'];
            $response = $this->tools->sefazCancela($chave, $xJust, $nProt);


            //padroniza os dados de retorno atraves da classe
            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();

            //em array do XML
            $arr = $stdCl->toArray();


            //em JSON do XML
            $json = $stdCl->toJson();

            //verifique se houve falha
            if ($std->cStat != 128) {
                throw new Exception($std->xMotivo, (int) $std->cStat);

            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                    $xml = Complements::toAuthorize($this->tools->lastRequest, $response);

                    return [
                        'sucesso' => true,
                        'codigo' => 1000,
                        'mensagem' => 'Protocolo recebido com sucesso',
                        'data' =>  $xml,
                    ];
                }else{
                    return [
                        'sucesso' => false,
                        'codigo' => 3000,
                        'mensagem' => 'Evento em duplicidade'
                    ];
                }
            }
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'codigo' => $e->getCode(),
                'motivo' => $e->getMessage(),
                'data' => null,
            ];
        }

    }

    public function cceDocument($request, $emitente)
    {

        try {
            $this->tools->model(55);

            $chave = $request['chave'];
            $xCorrecao = $request['correcao'];
            $nSeqEvento = (int)$request['da'];
            $response = $this->tools->sefazCCe($chave, $xCorrecao, $nSeqEvento);


            //padroniza os dados de retorno atraves da classe
            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();

            //em array do XML
            $arr = $stdCl->toArray();


            //em JSON do XML
            $json = $stdCl->toJson();

            //verifique se houve falha
            if ($std->cStat != 128) {
                throw new Exception($std->xMotivo, (int) $std->cStat);

            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '136' || $cStat == '135') {
                    $xml = Complements::toAuthorize($this->tools->lastRequest, $response);

                    $logo = null;

                    $daevento = new Daevento($xml, $emitente);
                    $daevento->debugMode(false);
                    $daevento->logoParameters($logo, 'R');
                    $pdf = $daevento->render();
                    return [
                        'sucesso' => true,
                        'codigo' => 1000,
                        'mensagem' => 'Protocolo recebido com sucesso',
                        'data' =>  $xml,
                        'pdf' => base64_encode($pdf)
                    ];
                }else{
                    return [
                        'sucesso' => false,
                        'codigo' => 3000,
                        'mensagem' => 'Evento em duplicidade'
                    ];
                }
            }
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'codigo' => $e->getCode(),
                'motivo' => $e->getMessage(),
                'data' => null,
            ];
        }

    }


    public function getErrors()
    {
        return $this->nfe->getErrors();
    }


    public function getChave()
    {
        return $this->nfe->getChave();
    }


}
