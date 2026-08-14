<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->foreignId('nivel_academico_id')->nullable()->after('institucion')
                ->constrained('niveles_academicos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nivel_academico_id');
        });
    }
};
