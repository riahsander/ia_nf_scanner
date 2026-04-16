<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotaFiscalController;

// 1. Rota inicial (Exibe o formulário de upload)
// Vi no seu print anterior que o seu arquivo de upload se chama "upload.blade.php"
Route::get('/', function () {
    return view('upload');
});

// 2. Rota que recebe a imagem e envia para a IA processar
Route::post('/enviar', [NotaFiscalController::class, 'store']);

// 3. Rota do Dashboard (Lista as notas salvas no banco)
Route::get('/notas', [App\Http\Controllers\NotaFiscalController::class, 'index']);

Route::delete('/notas/{id}', [NotaFiscalController::class, 'destroy'])->name('notas.destroy');
