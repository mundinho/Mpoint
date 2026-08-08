<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribuicao_aleatoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_premio')->cascadeOnDelete();
            $table->unsignedInteger('quantidade');
            $table->timestamp('data_programada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribuicao_aleatoria');
    }
};
