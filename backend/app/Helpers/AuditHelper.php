<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditHelper
{
    private const ACOES_CANONICAS = [
        'criacao'    => 'Criação',
        'criação'    => 'Criação',
        'edicao'     => 'Edição',
        'edição'     => 'Edição',
        'exclusao'   => 'Exclusão',
        'exclusão'   => 'Exclusão',
        'ativacao'   => 'Ativação',
        'ativação'   => 'Ativação',
        'inativacao' => 'Inativação',
        'inativação' => 'Inativação',
        'login'      => 'Login',
        'logout'     => 'Logout',
    ];

    public static function log(string $action, string $description): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => self::normalizarAcao($action),
            'description' => $description,
            'ip_address'  => Request::ip(),
        ]);
    }

    private static function normalizarAcao(string $action): string
    {
        $chave = mb_strtolower(trim($action));

        return self::ACOES_CANONICAS[$chave] ?? $action;
    }
}