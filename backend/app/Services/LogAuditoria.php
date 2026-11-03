<?php

namespace App\Services;

use App\Models\LogDeAuditoria;
use Illuminate\Support\Facades\Auth;

class LogAuditoria
{
    public static function registrar(
        string $nomeEntidade,
        int $idEntidade,
        string $acao,
        ?string $descricao = null,
        $dadosAntes = null,
        $dadosDepois = null
    ): void {
        LogDeAuditoria::create([
            'nome_entidade' => $nomeEntidade,
            'id_entidade'   => $idEntidade,
            'acao'          => $acao,
            'descricao'     => $descricao,
            'dados_antes'   => $dadosAntes  !== null ? json_encode($dadosAntes, JSON_UNESCAPED_UNICODE)  : null,
            'dados_depois'  => $dadosDepois !== null ? json_encode($dadosDepois, JSON_UNESCAPED_UNICODE) : null,
            'id_usuario'    => Auth::id(),
        ]);
    }
}