<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    // Apontando para o nome correto que acabamos de colocar na migration
    protected $table = 'notas_fiscais';

    protected $fillable = [
        'empresa_emissora', 'cnpj', 'data_emissao', 'valor_total', 'categoria', 'itens'
    ];

    protected $casts = [
        'itens' => 'array',
    ];
}
