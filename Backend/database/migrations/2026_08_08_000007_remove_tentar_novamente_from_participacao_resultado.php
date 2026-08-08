<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('participacao')->where('resultado', 'tentar_novamente')->update(['resultado' => 'nao_vencedor']);

        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor','tentar_novamente') NOT NULL DEFAULT 'pendente'");
    }
};
