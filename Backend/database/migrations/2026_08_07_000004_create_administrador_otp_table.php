<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrador_otp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrador_id')->constrained('administradores')->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->timestamp('expira_em');
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->timestamp('validado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrador_otp');
    }
};
