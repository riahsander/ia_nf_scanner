<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotaFiscalController;

// 1. Página Inicial (Upload)
Route::get('/', function () {
    return view('upload');
})->name('upload');

// 2. Processamento (POST)
Route::post('/enviar', [NotaFiscalController::class, 'store'])->name('notas.store');

// 3. Histórico (GET) - Onde o index.blade.php será executado
Route::get('/notas', [NotaFiscalController::class, 'index'])->name('notas.index');

// 4. Buscar Detalhes da Nota (Nova Rota para o Modal/Olhinho)
// Esta rota retorna o JSON com os itens para o JavaScript
Route::get('/notas/{id}', [NotaFiscalController::class, 'show'])->name('notas.show');

// 5. Excluir
Route::delete('/notas/{id}', [NotaFiscalController::class, 'destroy'])->name('notas.destroy');
