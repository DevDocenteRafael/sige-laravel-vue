<?php

namespace App\Services;

class ChaveAcessoNfe
{
    public static function normalizar(?string $valor): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $valor));
    }

    public static function digitoVerificador(string $base): int
    {
        $soma = 0;
        $peso = 2;

        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $soma += (ord($base[$i]) - 48) * $peso;
            $peso  = $peso === 9 ? 2 : $peso + 1;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }

    public static function validar(string $chave): ?string
    {
        $tamanho = strlen($chave);
        if ($tamanho !== 44) {
            return "A chave de acesso precisa ter 44 caracteres (recebi {$tamanho}).";
        }

        if (!preg_match('/^[0-9]{6}[A-Z0-9]{12}[0-9]{26}$/', $chave)) {
            return 'A chave de acesso tem caracteres inválidos: letras só são aceitas nas posições 7 a 18 (CNPJ do emitente).';
        }

        if (self::digitoVerificador(substr($chave, 0, 43)) !== (int) $chave[43]) {
            return 'Dígito verificador da chave inválido. A leitura pode ter falhado: bipe o código de barras de novo.';
        }

        $mes = (int) substr($chave, 4, 2);
        if ($mes < 1 || $mes > 12) {
            return 'Mês de emissão inválido na chave de acesso.';
        }

        $modelo = substr($chave, 20, 2);
        if ($modelo !== '55') {
            return "Esta chave é do modelo {$modelo}, e o recebimento aceita só NF-e (modelo 55).";
        }

        return null;
    }
}
