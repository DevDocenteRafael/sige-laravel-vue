<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produto')->nullable()->after('id_lote');
        });

        $grupos = DB::table('item_lote')
            ->select('nome', 'sku', DB::raw('MIN(categoria) as categoria'), DB::raw('MIN(fornecedor) as fornecedor'),
                     DB::raw('MIN(unidade_medida) as unidade_medida'), DB::raw('MIN(estoque_minimo) as estoque_minimo'))
            ->groupBy('nome', 'sku')
            ->get();

        foreach ($grupos as $grupo) {
            $idProduto = DB::table('produto')->where('sku', $grupo->sku)->value('id_produto');

            if (!$idProduto) {
                $idProduto = DB::table('produto')->insertGetId([
                    'nome'           => $grupo->nome,
                    'sku'            => $grupo->sku,
                    'unidade_medida' => $grupo->unidade_medida ?? 'UN',
                    'estoque_minimo' => $grupo->estoque_minimo ?? 0,
                    'estoque_atual'  => 0,
                    'id_categoria'   => null,
                    'id_fornecedor'  => null,
                ], 'id_produto');
            }

            DB::table('item_lote')
                ->where('nome', $grupo->nome)
                ->where('sku', $grupo->sku)
                ->update(['id_produto' => $idProduto]);
        }

        DB::statement('
        UPDATE produto
        SET estoque_atual = (
        SELECT COALESCE(SUM(il.quantidade), 0)
        FROM item_lote il
        WHERE il.id_produto = produto.id_produto
        )
        ');

        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produto')->nullable(false)->change();
            $table->foreign('id_produto')->references('id_produto')->on('produto')->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->dropForeign(['id_produto']);
            $table->dropColumn('id_produto');
        });
    }
};