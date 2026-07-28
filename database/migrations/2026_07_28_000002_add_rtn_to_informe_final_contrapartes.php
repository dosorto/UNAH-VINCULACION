<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_contrapartes', function (Blueprint $table) {
            $table->string('rtn', 20)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_contrapartes', function (Blueprint $table) {
            $table->dropColumn('rtn');
        });
    }
};
