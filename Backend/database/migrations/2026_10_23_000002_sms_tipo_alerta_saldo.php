<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// SMS de aviso de saldo baixo (enviado aos contactos de alerta, sem participante nem administrador)
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sms MODIFY tipo ENUM('otp','vencedor','nao_vencedor','admin_otp','alerta_saldo') NOT NULL");
    }

    public function down(): void
    {
        DB::table('sms')->where('tipo', 'alerta_saldo')->delete();
        DB::statement("ALTER TABLE sms MODIFY tipo ENUM('otp','vencedor','nao_vencedor','admin_otp') NOT NULL");
    }
};
