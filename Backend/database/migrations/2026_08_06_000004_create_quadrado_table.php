<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quadrado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->foreignId('premio_id')->nullable()->constrained('premio')->nullOnDelete();
            $table->enum('estado', ['disponivel', 'aberto'])->default('disponivel');
            $table->foreignId('aberto_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('aberto_em')->nullable();
            $table->timestamps();

            $table->unique(['campanha_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quadrado');
    }
};
