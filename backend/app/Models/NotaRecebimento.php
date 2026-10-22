<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NotaRecebimento extends Model {
    protected $table      = 'nota_recebimento';
    protected $primaryKey = 'id_nota';

    protected $fillable = [
        'chave_acesso', 'numero', 'serie', 'data_emissao', 'fornecedor_cnpj',
        'fornecedor_nome', 'id_pedido', 'numero_pedido', 'valor_total',
        'status', 'xml_path', 'id_usuario',
    ];

    protected $casts = [
        'data_emissao' => 'date',
    ];

    public function itens() {
        return $this->hasMany(NotaItem::class, 'id_nota', 'id_nota');
    }

    public function pedido() {
        return $this->belongsTo(Pedido::class, 'id_pedido', 'id_pedido');
    }
}