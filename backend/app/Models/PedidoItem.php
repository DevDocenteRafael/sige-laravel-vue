<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model {
    protected $table      = 'pedido_item';
    protected $primaryKey = 'id_pedido_item';
    public $timestamps    = false;

    protected $fillable = [
        'id_pedido', 'id_produto', 'sku', 'descricao', 'unidade',
        'quantidade_pedida', 'quantidade_recebida', 'valor_unitario',
    ];

    public function pedido() {
        return $this->belongsTo(Pedido::class, 'id_pedido', 'id_pedido');
    }

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }
}