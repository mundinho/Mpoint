<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanha_premio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->foreignId('premio_banco_id')->constrained('premios_banco')->restrictOnDelete();
            $table->string('modo_distribuicao');
            $table->unsignedInteger('quantidade')->nullable();
            $table->string('logica_aleatoriedade')->nullable();
            $table->timestamp('data_programada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanha_premio');
    }
};
