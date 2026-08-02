<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('informe_final_ods', function (Blueprint $table) { $table->string('origen', 20)->default('PLANIFICADO')->after('nivel_contribucion'); }); } public function down(): void { Schema::table('informe_final_ods', function (Blueprint $table) { $table->dropColumn('origen'); }); } };
