<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participante_campanha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->unsignedInteger('tentativas_disponiveis')->default(1);
            $table->unsignedInteger('tentativas_usadas')->default(0);
            $table->timestamps();

            $table->unique(['usuario_id', 'campanha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participante_campanha');
    }
};
