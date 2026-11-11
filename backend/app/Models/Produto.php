<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model {
    protected $table      = 'produto';
    protected $primaryKey = 'id_produto';
    public $timestamps    = false;
    protected $fillable   = ['nome', 'sku', 'unidade_medida', 'preco_custo', 'estoque_minimo', 'percentual_alerta', 'estoque_atual', 'prioridade_abc', 'id_categoria', 'id_fornecedor', 'id_centro_custo'];

    public function categoria() {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function fornecedor() {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }

    public function centroCusto() {
        return $this->belongsTo(CentroCusto::class, 'id_centro_custo', 'id_centro_custo');
    }

    public function lotes() {
        return $this->hasMany(Lote::class, 'id_produto');
    }

    public function itensLote() {
        return $this->hasMany(ItemLote::class, 'id_produto');
    }

    public function proximoVencimento() {
        return $this->hasOne(ItemLote::class, 'id_produto')
            ->whereNotNull('data_validade')
            ->orderBy('data_validade', 'asc');
    }

    public function statusEstoque(): string
    {
        $qtd = $this->itens_lote_sum_quantidade ?? $this->estoque_atual ?? 0;
        $min = $this->estoque_minimo ?? 0;

        if ($qtd <= 0) {
            return 'sem_estoque';
        }

        if ($min <= 0) {
            return 'ok';
        }

        if ($qtd <= $min) {
            return 'critico';
        }

        $percentual   = $this->percentual_alerta ?? 20;
        $limiteAlerta = $min * (1 + $percentual / 100);

        if ($qtd <= $limiteAlerta) {
            return 'atencao';
        }

        return 'ok';
    }
}