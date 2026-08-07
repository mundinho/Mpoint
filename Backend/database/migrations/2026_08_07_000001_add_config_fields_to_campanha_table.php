<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campanha', function (Blueprint $table) {
            $table->string('nome')->nullable()->after('id');
            $table->timestamp('data_fim')->nullable()->after('data_inicio');
            $table->unsignedInteger('otp_validade_minutos')->default(5)->after('total_premios');
        });

        DB::statement("ALTER TABLE campanha MODIFY estado ENUM('ativa','pausada','encerrada') NOT NULL DEFAULT 'ativa'");
    }

    public function down(): void
    {
        Schema::table('campanha', function (Blueprint $table) {
            $table->dropColumn(['nome', 'data_fim', 'otp_validade_minutos']);
        });

        DB::statement("ALTER TABLE campanha MODIFY estado ENUM('ativa','encerrada') NOT NULL DEFAULT 'ativa'");
    }
};
