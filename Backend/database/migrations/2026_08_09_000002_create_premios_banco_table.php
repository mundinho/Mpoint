<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premios_banco', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->unsignedInteger('quantidade_padrao')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premios_banco');
    }
};
