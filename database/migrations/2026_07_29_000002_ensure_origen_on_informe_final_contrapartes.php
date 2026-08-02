<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('informe_final_contrapartes') || Schema::hasColumn('informe_final_contrapartes', 'origen')) {
            return;
        }

        Schema::table('informe_final_contrapartes', function (Blueprint $table): void {
            $table->string('origen', 20)->nullable()->default('PLANIFICADO')->after('documento_respaldo');
        });
        DB::table('informe_final_contrapartes')->whereNull('origen')->update(['origen' => 'PLANIFICADO']);
    }

    public function down(): void
    {
        if (Schema::hasTable('informe_final_contrapartes') && Schema::hasColumn('informe_final_contrapartes', 'origen')) {
            Schema::table('informe_final_contrapartes', fn (Blueprint $table) => $table->dropColumn('origen'));
        }
    }
};
