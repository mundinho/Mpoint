<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participacao', function (Blueprint $table) {
            $table->dropUnique(['campanha_id', 'usuario_id']);
            $table->index(['campanha_id', 'usuario_id']);
        });

        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor','tentar_novamente') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        Schema::table('participacao', function (Blueprint $table) {
            $table->dropIndex(['campanha_id', 'usuario_id']);
            $table->unique(['campanha_id', 'usuario_id']);
        });

        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor') NOT NULL DEFAULT 'pendente'");
    }
};
