<?php

namespace App\Console\Commands;

use App\Models\Produto;
use Illuminate\Console\Command;

class RecalcularEstoqueProdutos extends Command
{
    protected $signature = 'estoque:recalcular {--dry-run : Mostra as diferenças sem gravar no banco}';

    protected $description = 'Recalcula produto.estoque_atual somando as quantidades reais em item_lote';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $produtos = Produto::withSum('itensLote as soma_itens', 'quantidade')->get();

        $alterados = 0;

        $this->table(
            ['ID', 'Produto', 'Estoque atual (coluna)', 'Estoque real (soma itens)', 'Diferença'],
            $produtos->map(function ($produto) {
                $real = $produto->soma_itens ?? 0;
                $diferenca = $real - $produto->estoque_atual;
                return [
                    $produto->id_produto,
                    $produto->nome,
                    $produto->estoque_atual,
                    $real,
                    $diferenca === 0 ? '—' : ($diferenca > 0 ? "+{$diferenca}" : $diferenca),
                ];
            })->filter(fn ($linha) => $linha[4] !== '—')->values()
        );

        foreach ($produtos as $produto) {
            $real = $produto->soma_itens ?? 0;

            if ($real !== $produto->estoque_atual) {
                $alterados++;

                if (!$dryRun) {
                    $produto->update(['estoque_atual' => $real]);
                }
            }
        }

        if ($alterados === 0) {
            $this->info('Nenhuma divergência encontrada — todos os produtos já estão corretos.');
            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "{$alterados} produto(s) com divergência (nada foi alterado — remova --dry-run pra aplicar)."
            : "{$alterados} produto(s) corrigido(s) com sucesso.");

        return self::SUCCESS;
    }
}