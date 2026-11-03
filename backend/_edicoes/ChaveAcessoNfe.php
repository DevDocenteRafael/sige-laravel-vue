<?php

namespace App\Services;

/**
 * Chave de acesso da NF-e (44 caracteres).
 *
 * Estrutura: cUF(2) AAMM(4) CNPJ(14, pode ter letras) modelo(2) série(3)
 *            número(9) tpEmis(1) cNF(8) DV(1)
 *
 * O DV é módulo 11 (pesos 2 a 9, da direita para a esquerda) sobre o valor
 * ASCII de cada caractere menos 48. Para chaves só numéricas isso é igual ao
 * cálculo antigo; o mesmo cálculo cobre o CNPJ alfanumérico.
 */
class ChaveAcessoNfe
{
    /** Remove separadores/espaços e deixa em maiúsculas (leitor ou colagem podem trazê-los). */
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

    /**
     * Devolve a mensagem do problema, ou null se a chave é uma chave de NF-e válida.
     * Espera a chave já normalizada.
     */
    public static function validar(string $chave): ?string
    {
        $tamanho = strlen($chave);
        if ($tamanho !== 44) {
            return "A chave de acesso precisa ter 44 caracteres (recebi {$tamanho}).";
        }

        // Letras só podem aparecer nas 12 primeiras posições do CNPJ (posições 7 a 18)
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
