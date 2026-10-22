<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FornecedorProduto extends Model {
    protected $table      = 'fornecedor_produto';
    protected $primaryKey = 'id_fornecedor_produto';

    protected $fillable = [
        'fornecedor_cnpj', 'codigo_fornecedor', 'descricao_fornecedor', 'id_produto',
    ];

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }
}