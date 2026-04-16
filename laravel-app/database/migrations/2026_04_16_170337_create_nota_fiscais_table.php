<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aqui o nome da tabela já está certinho em português
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_emissora')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('data_emissao')->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->string('categoria')->nullable();
            $table->json('itens')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Aqui também alteramos para apagar a tabela correta se necessário
        Schema::dropIfExists('notas_fiscais');
    }
};
