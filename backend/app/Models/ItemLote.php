<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ItemLote extends Model {
    protected $table      = 'item_lote';
    protected $primaryKey = 'id_item';
    public $timestamps    = false;

    protected $fillable = [
        'id_lote', 'id_produto', 'quantidade',
        'unidade_medida', 'data_validade', 'localizacao',
        'prioridade_abc', 'prioridade_manual', 'ordem',
        'id_centro_custo',
    ];

    protected $casts = [
        'data_validade' => 'date',
    ];

    protected static function booted()
    {
        static::created(function (ItemLote $item) {
            static::sincronizarEstoque($item->id_produto);
        });

        static::updated(function (ItemLote $item) {
            static::sincronizarEstoque($item->id_produto);

            if ($item->wasChanged('id_produto')) {
                static::sincronizarEstoque($item->getOriginal('id_produto'));
            }
        });

        static::deleted(function (ItemLote $item) {
            static::sincronizarEstoque($item->id_produto);
        });
    }

    public static function sincronizarEstoque($idProduto): void
    {
        if (!$idProduto) {
            return;
        }

        $soma = static::where('id_produto', $idProduto)->sum('quantidade');

        Produto::where('id_produto', $idProduto)->update(['estoque_atual' => $soma]);
    }

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }

    public function lote() {
        return $this->belongsTo(Lote::class, 'id_lote');
    }

    public function centroCusto() {
        return $this->belongsTo(CentroCusto::class, 'id_centro_custo', 'id_centro_custo');
    }
}