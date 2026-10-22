<?php

namespace App\Services;

/**
 * Sugere qual item do pedido corresponde à descrição de um item da nota,
 * comparando as palavras das duas descrições.
 */
class DescricaoMatcher
{
    private const IGNORAR = ['DE', 'DA', 'DO', 'DAS', 'DOS', 'PARA', 'COM', 'SEM', 'EM', 'E', 'A', 'O', 'OU', 'NA', 'NO'];

    /**
     * @param array $candidatos lista de ['id' => ..., 'descricao' => ...]
     * @return array lista de ['id' => ..., 'pontuacao' => 0..1], melhores primeiro
     */
    public function sugerir(string $descricaoNota, array $candidatos, int $limite = 5): array
    {
        $tokensNota = $this->tokens($descricaoNota);
        if (empty($tokensNota)) {
            return [];
        }

        $resultado = [];
        foreach ($candidatos as $c) {
            $tokensPedido = $this->tokens($c['descricao']);
            if (empty($tokensPedido)) {
                continue;
            }

            $comuns = count(array_intersect($tokensNota, $tokensPedido));
            if ($comuns === 0) {
                continue;
            }

            $cobertura = $comuns / count($tokensNota);
            $jaccard   = $comuns / count(array_unique(array_merge($tokensNota, $tokensPedido)));

            $resultado[] = [
                'id'        => $c['id'],
                'pontuacao' => round($cobertura + $jaccard / 100, 4),
            ];
        }

        usort($resultado, fn ($a, $b) => $b['pontuacao'] <=> $a['pontuacao']);

        return array_slice($resultado, 0, $limite);
    }

    private function tokens(string $texto): array
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
        ]);

        $palavras = preg_split('/[^A-Z0-9]+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];
        foreach ($palavras as $p) {
            if (in_array($p, self::IGNORAR, true)) {
                continue;
            }
            // tira o "S" do plural (OLHOS -> OLHO), sem mexer em palavras curtas ou números
            if (strlen($p) > 3 && substr($p, -1) === 'S' && !ctype_digit($p)) {
                $p = substr($p, 0, -1);
            }
            $tokens[] = $p;
        }

        return array_values(array_unique($tokens));
    }
}