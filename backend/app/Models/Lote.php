<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemLote;

class Lote extends Model {
    protected $table      = 'lote';
    protected $primaryKey = 'id_lote';
    public $timestamps    = false;
    protected $fillable   = ['numero_lote', 'quantidade', 'status', 'data_entrada', 'data_validade', 'descricao', 'id_produto', 'id_localizacao'];

    /**
     * Guarda, antes da exclusão, quais produtos tinham itens neste lote,
     * para recalcular o estoque_atual deles depois que os itens forem apagados
     * (inclusive quando o banco apaga os itens em cascata).
     */
    protected static array $produtosAfetados = [];

    protected static function booted()
    {
        static::deleting(function (Lote $lote) {
            static::$produtosAfetados[$lote->getKey()] = ItemLote::where('id_lote', $lote->getKey())
                ->pluck('id_produto')
                ->unique()
                ->all();
        });

        static::deleted(function (Lote $lote) {
            foreach (static::$produtosAfetados[$lote->getKey()] ?? [] as $idProduto) {
                ItemLote::sincronizarEstoque($idProduto);
            }
            unset(static::$produtosAfetados[$lote->getKey()]);
        });
    }

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto')->with('categoria');
    }
    public function localizacao() {
        return $this->belongsTo(Localizacao::class, 'id_localizacao');
    }
    public function movimentacoes() {
        return $this->hasMany(Movimentacao::class, 'id_lote');
    }
    public function itens() {
        return $this->hasMany(ItemLote::class, 'id_lote');
    }
}