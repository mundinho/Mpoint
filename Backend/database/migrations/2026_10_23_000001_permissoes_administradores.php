<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permissões dos administradores:
 *  - master: gere os administradores (e tem acesso a todas as campanhas);
 *  - todas_campanhas: acesso a todas as campanhas, actuais e futuras;
 *  - administrador_campanha: campanhas de quem não tem acesso a todas.
 *
 * Os administradores existentes mantêm o acesso que já tinham (todas as campanhas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administradores', function (Blueprint $table) {
            $table->boolean('master')->default(false)->after('ativo');
            $table->boolean('todas_campanhas')->default(false)->after('master');
        });

        DB::table('administradores')->update(['todas_campanhas' => true]);

        Schema::create('administrador_campanha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrador_id')->constrained('administradores')->cascadeOnDelete();
            $table->foreignId('campanha_id')->constrained('campanha')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['administrador_id', 'campanha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrador_campanha');

        Schema::table('administradores', function (Blueprint $table) {
            $table->dropColumn(['master', 'todas_campanhas']);
        });
    }
};
