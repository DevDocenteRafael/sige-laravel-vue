<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadeEquivalencia extends Model
{
    protected $table      = 'unidade_equivalencia';
    protected $primaryKey = 'id_equivalencia';
    protected $fillable   = ['unidade_nota', 'unidade_pedido', 'id_usuario'];
}