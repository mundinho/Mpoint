<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanha', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('total_quadrados')->default(1000);
            $table->unsignedInteger('total_premios')->default(10);
            $table->enum('estado', ['ativa', 'encerrada'])->default('ativa');
            $table->timestamp('data_inicio')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanha');
    }
};
