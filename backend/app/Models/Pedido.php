<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model {
    protected $table      = 'pedido';
    protected $primaryKey = 'id_pedido';

    protected $fillable = [
        'numero', 'id_fornecedor', 'fornecedor_nome', 'fornecedor_cnpj',
        'data_emissao', 'valor_total', 'status', 'observacao', 'id_usuario',
    ];

    protected $casts = [
        'data_emissao' => 'date',
    ];

    public function itens() {
        return $this->hasMany(PedidoItem::class, 'id_pedido', 'id_pedido');
    }

    public function fornecedor() {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }
}