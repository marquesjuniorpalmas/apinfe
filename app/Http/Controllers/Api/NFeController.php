<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Services\dfe\nfe\NFeService;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;


class NFeController extends Controller
{
    use ApiResponser;


    public function gerarNFe(Request $request)
    {
        try {
            $emitente = auth()->user()->emitente;

            $nfeService = new NFeService($emitente, '55');

            $data = $nfeService->sendAndAuthorizeNfe($request);

            return response()->json($data);

        } catch (Exception $e) {


//            return [
//                'sucesso' => false,
//                'codigo' => $e->getCode(),
//                'mensagem' => 'Vai se fuder',
//                'data' => null
//            ];
        }

    }


    public function consultaDfe($chave)
    {
        if(!$chave) {
            throw  new Exception('O parâmetro chave é obrigatório', 9003);
        }

        if(strlen($chave) !== 44) {
            throw  new Exception('A chave informada é inválida. Deve ter 44 caracteres', 9004);
        }

        $documento = Documento::where('chave', $chave)->get();

        if(!$documento) {
            throw  new Exception('O documento solicitado não foi encontrado', 9005);
        }

        return response()->json([
            'sucesso' => true,
            'codigo' => 1000,
            'mensagem' => 'Solicitação processada.',
            'data' => json_decode(json_encode($documento))
        ]);
    }

    public function cancelaDfe(Request $request)
    {
        try {
            $emitente = auth()->user()->emitente;

            $nfeService = new NFeService($emitente, '55');

            $data = $nfeService->cancelDocument($request->all());

            return response()->json($data);

        } catch (Exception $e) {


            return [
                'sucesso' => false,
                'codigo' => $e->getCode(),
                'mensagem' => 'Erro no processamento',
                'data' => null
            ];
        }

    }

    public function correcaoDfe(Request $request)
    {
        try {
            $emitente = auth()->user()->emitente;


            $nfeService = new NFeService($emitente, '55');

            $dadosEmitente = [
                'razao' => $emitente->razao_social,
                'logradouro' => $emitente->logradouro,
                'numero' => $emitente->numero,
                'complemento' => $emitente->complemento,
                'bairro' => $emitente->bairro,
                'CEP' => $emitente->codigo_postal,
                'municipio' => '',
                'UF' => '',
                'telefone' => $emitente->telefone,
                'email' => $emitente->email
            ];

            $data = $nfeService->cceDocument($request->all(), $dadosEmitente);

            return response()->json($data);

        } catch (Exception $e) {


            return [
                'sucesso' => false,
                'codigo' => $e->getCode(),
                'mensagem' => 'Erro no processamento',
                'data' => null
            ];
        }

    }



}
