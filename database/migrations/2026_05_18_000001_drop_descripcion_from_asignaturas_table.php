<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asignaturas') && Schema::hasColumn('asignaturas', 'descripcion')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asignaturas') && !Schema::hasColumn('asignaturas', 'descripcion')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }
    }
};
