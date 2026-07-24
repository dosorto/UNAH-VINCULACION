<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('asignaturas', 'creditos_academicos')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->decimal('creditos_academicos', 5, 2)->default(0)->after('nombre');
            });
        }

        if (! Schema::hasColumn('asignaturas', 'horas_academicas')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->unsignedInteger('horas_academicas')->default(0)->after('creditos_academicos');
            });
        }

        if (! Schema::hasColumn('asignaturas', 'ruta_documento_descripcion_minima')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->string('ruta_documento_descripcion_minima', 500)->nullable()->after('horas_academicas');
            });
        }

        if (! Schema::hasColumn('asignaturas', 'activa')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->boolean('activa')->default(true)->after('ruta_documento_descripcion_minima');
            });
        }
    }

    public function down(): void
    {
        $columns = collect([
            'creditos_academicos',
            'horas_academicas',
            'ruta_documento_descripcion_minima',
            'activa',
        ])->filter(fn (string $column) => Schema::hasColumn('asignaturas', $column))->all();

        if ($columns === []) {
            return;
        }

        Schema::table('asignaturas', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
