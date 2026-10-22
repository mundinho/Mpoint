<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O OTP passa a pertencer a uma campanha e, depois de validado, emite um token de
 * participação (guardado só como hash) exigido para abrir um número.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp', function (Blueprint $table) {
            $table->foreignId('campanha_id')->nullable()->after('usuario_id')->constrained('campanha')->nullOnDelete();
            $table->string('token_hash', 64)->nullable()->after('validado_em');
            $table->timestamp('token_expira_em')->nullable()->after('token_hash');
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('otp', function (Blueprint $table) {
            $table->dropIndex(['token_hash']);
            $table->dropForeign(['campanha_id']);
            $table->dropColumn(['campanha_id', 'token_hash', 'token_expira_em']);
        });
    }
};
