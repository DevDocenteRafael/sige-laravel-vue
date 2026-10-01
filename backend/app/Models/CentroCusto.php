<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCusto extends Model
{
    protected $table = 'centro_custo';
    protected $primaryKey = 'id_centro_custo';
    protected $fillable = ['codigo', 'nome', 'ativo'];
    protected $casts = ['ativo' => 'boolean'];
}