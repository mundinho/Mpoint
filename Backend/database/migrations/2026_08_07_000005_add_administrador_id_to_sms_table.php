<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms', function (Blueprint $table) {
            $table->foreignId('administrador_id')->nullable()->after('usuario_id')->constrained('administradores')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sms MODIFY usuario_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE sms MODIFY tipo ENUM('otp','vencedor','nao_vencedor','admin_otp') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('sms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('administrador_id');
        });
    }
};
