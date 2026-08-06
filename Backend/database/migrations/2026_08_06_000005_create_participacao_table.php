<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('quadrado_id')->constrained('quadrado')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->enum('resultado', ['pendente', 'vencedor', 'nao_vencedor'])->default('pendente');
            $table->foreignId('premio_id')->nullable()->constrained('premio')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campanha_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participacao');
    }
};
