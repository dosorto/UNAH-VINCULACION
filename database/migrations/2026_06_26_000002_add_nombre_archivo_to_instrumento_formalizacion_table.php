<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrumento_formalizacion', function (Blueprint $table) {
            $table->string('nombre_archivo')->nullable()->after('documento_url');
        });
    }

    public function down(): void
    {
        Schema::table('instrumento_formalizacion', function (Blueprint $table) {
            $table->dropColumn('nombre_archivo');
        });
    }
};
