<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NotaItem extends Model {
    protected $table      = 'nota_item';
    protected $primaryKey = 'id_nota_item';
    public $timestamps    = false;

    protected $fillable = [
        'id_nota', 'codigo_fornecedor', 'descricao', 'unidade', 'quantidade',
        'valor_unitario', 'id_produto', 'id_pedido_item', 'status',
        'quantidade_entrada', 'lote_fornecedor', 'data_validade',
    ];

    protected $casts = [
        'data_validade' => 'date',
    ];

    public function nota() {
        return $this->belongsTo(NotaRecebimento::class, 'id_nota', 'id_nota');
    }

    public function pedidoItem() {
        return $this->belongsTo(PedidoItem::class, 'id_pedido_item', 'id_pedido_item');
    }

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }
}