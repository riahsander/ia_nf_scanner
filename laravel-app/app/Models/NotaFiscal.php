<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    use HasFactory;

    // Nome da tabela conforme definido na sua migration
    protected $table = 'notas_fiscais';

    // Campos que podem ser preenchidos em massa (Mass Assignment)
    protected $fillable = [
        'empresa_emissora',
        'cnpj',
        'data_emissao',
        'valor_total',
        'categoria',
        'itens',
    ];

    /**
     * O campo 'itens' é salvo como JSON no banco de dados.
     * Este cast garante que o Laravel o transforme em um Array PHP
     * automaticamente ao ler os dados, e em JSON ao salvar.
     */
    protected $casts = [
        'itens' => 'array',
        'data_emissao' => 'date',
        'valor_total' => 'float',
    ];
}
