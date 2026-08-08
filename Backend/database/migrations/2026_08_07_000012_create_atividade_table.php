<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atividade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->nullable()->constrained('campanha')->nullOnDelete();
            $table->enum('tipo', ['registo', 'validacao', 'participacao', 'vencedor', 'tentar_novamente', 'premio_entregue']);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->unsignedSmallInteger('numero')->nullable();
            $table->enum('resultado', ['pendente', 'vencedor', 'nao_vencedor', 'tentar_novamente'])->nullable();
            $table->foreignId('premio_id')->nullable()->constrained('premio')->nullOnDelete();
            $table->string('descricao')->nullable();
            $table->timestamps();

            $table->index(['campanha_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atividade');
    }
};
