<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NotasController extends Controller
{
    public function obterNotasDaAPI()
    {
        $response = Http::get('http://homologacao3.azapfy.com.br/api/ps/notas');

        if ($response->successful()) {
            return $response->json();
        } else {
            return response()->json(['error' => 'Erro ao acessar a API'], 500);
        }
    }

    public function agruparNotasPorRemetente()
    {
        $notas = $this->obterNotasDaAPI();

        $notasAgrupadas = [];

        foreach ($notas as $nota) {
            $remetente = $nota['nome_remete'];

            if (array_key_exists($remetente, $notasAgrupadas)) {
                $notasAgrupadas[$remetente]['valor_total'] += $nota['valor'];
                $notasAgrupadas[$remetente]['notas'][] = $nota;
            } else {
                $notasAgrupadas[$remetente] = [
                    'valor_total' => round($nota['valor'], 2),
                    'notas' => [$nota]
                ];
            }
        }

        return response()->json($notasAgrupadas, 200);
    }

    public function calcularValorRecebido($notas, $status)
    {
        $valorRecebido = 0;

        foreach ($notas as $nota) {
            if ($nota['status'] === $status) {
                $valorRecebido += $nota['valor'];
            }
        }

        return $valorRecebido;
    }

    public function calcularAtraso($dataEmissao, $dataEntrega)
    {
        $dataEmissao = Carbon::createFromFormat('d/m/Y H:i:s', $this->formatarData($dataEmissao));
        $dataEntrega = Carbon::createFromFormat('d/m/Y H:i:s', $this->formatarData($dataEntrega));

        $diasAtraso = $dataEmissao->diffInDays($dataEntrega) - 2;

        return max(0, $diasAtraso);
    }

    public function formatarData($data)
    {
        // Replace the backslashes with forward slashes
        $data_formatada = str_replace("\\/", "/", $data);
        // Format the date in the desired format
        $data_formatada = Carbon::createFromFormat('d/m/Y H:i:s', $data_formatada)->format('d/m/Y H:i:s');

        return $data_formatada;
    }


    public function calcularValores()
    {
        $notas = $this->obterNotasDaAPI();

        $notasAgrupadas = [];
        $resultado = [];

        foreach ($notas as $nota) {
            $remetente = $nota['nome_remete'];

            if (array_key_exists($remetente, $notasAgrupadas)) {
                $notasAgrupadas[$remetente]['valor_total'] += $nota['valor'];
                $notasAgrupadas[$remetente]['notas'][] = $nota;
            } else {
                $notasAgrupadas[$remetente] = [
                    'valor_total' => $nota['valor'],
                    'notas' => [$nota]
                ];
            }
        }


        foreach ($notasAgrupadas as $remetente => $info) {
            $valorTotal = $info['valor_total'];
            $notasEntregues = array_filter($info['notas'], function ($nota) {
                return $nota['status'] === 'COMPROVADO';
            });
            $valorRecebido = $this->calcularValorRecebido($notasEntregues, 'COMPROVADO');
            $valorNaoRecebido = $valorTotal - $valorRecebido;

            $valorAtraso = 0;
            foreach ($info['notas'] as $nota) {
                if ($nota['status'] === 'COMPROVADO') {
                    $atraso = $this->calcularAtraso($nota['dt_emis'], $nota['dt_entrega']);
                    $valorAtraso += $atraso * $nota['valor'];
                }
            }

            $resultado[] = [
                'remetente' => $remetente,
                'valor_total' => round($valorTotal, 2),
                'valor_recebido' => round($valorRecebido, 2),
                'valor_nao_recebido' => round($valorNaoRecebido, 2),
                'valor_atraso' => round($valorAtraso, 2)
            ];
        }

        return response()->json($resultado, 200);
    }
}
