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

    // liga o item de lote ao produto-mãe (identidade única)
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