<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $oldTableExists = Schema::hasTable('entidad_contraparte');
        $pivotTableExists = Schema::hasTable('entidad_contraparte_proyecto');
        

        // ─────────────────────────────────────────────────────────
        // 1. Rename old table to pivot (only if old table exists)
        // ─────────────────────────────────────────────────────────
        if ($oldTableExists && !$pivotTableExists) {
            // Drop FK from instrumento_formalizacion before renaming
            if (Schema::hasColumn('instrumento_formalizacion', 'entidad_contraparte_id')) {
                $fks = DB::select(
                    "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'instrumento_formalizacion'
                     AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
                );
                foreach ($fks as $fk) {
                    try {
                        DB::statement("ALTER TABLE `instrumento_formalizacion` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Throwable $e) {}
                }
            }

            // Drop FK from informe_final_contrapartes before renaming
            if (Schema::hasTable('informe_final_contrapartes') && Schema::hasColumn('informe_final_contrapartes', 'entidad_contraparte_id')) {
                $fks = DB::select(
                    "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'informe_final_contrapartes'
                     AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
                );
                foreach ($fks as $fk) {
                    try {
                        DB::statement("ALTER TABLE `informe_final_contrapartes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Throwable $e) {}
                }
            }

            Schema::rename('entidad_contraparte', 'entidad_contraparte_proyecto');
        }

        // ─────────────────────────────────────────────────────────
        // 2. Drop aporte column from pivot (legacy NOT NULL)
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('entidad_contraparte_proyecto') && Schema::hasColumn('entidad_contraparte_proyecto', 'aporte')) {
            Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
                $table->dropColumn('aporte');
            });
        }

        // ─────────────────────────────────────────────────────────
        // 2b. Make legacy columns nullable on pivot
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('entidad_contraparte_proyecto')) {
            Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
                if (Schema::hasColumn('entidad_contraparte_proyecto', 'nombre')) {
                    $table->string('nombre')->nullable()->change();
                }
                if (Schema::hasColumn('entidad_contraparte_proyecto', 'nombre_contacto')) {
                    $table->string('nombre_contacto')->nullable()->change();
                }
                if (Schema::hasColumn('entidad_contraparte_proyecto', 'correo')) {
                    $table->string('correo')->nullable()->change();
                }
                if (Schema::hasColumn('entidad_contraparte_proyecto', 'telefono')) {
                    $table->string('telefono')->nullable()->change();
                }
            });
        }

        // ─────────────────────────────────────────────────────────
        // 3. Create new catalog table (only if it doesn't exist)
        // ─────────────────────────────────────────────────────────
        if (!Schema::hasTable('entidad_contraparte')) {
            Schema::create('entidad_contraparte', function (Blueprint $table) {
                $table->id();
                $table->string('rtn', 20)->nullable();
                $table->string('nombre');
                $table->string('tipo_entidad')->nullable();
                $table->string('nombre_contacto')->nullable();
                $table->string('cargo_contacto')->nullable();
                $table->string('correo')->nullable();
                $table->string('telefono')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('rtn');
            });
        }

        // ─────────────────────────────────────────────────────────
        // 4. Add FK and rtn to pivot (if missing)
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('entidad_contraparte_proyecto')) {
            Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
                if (!Schema::hasColumn('entidad_contraparte_proyecto', 'entidad_contraparte_id')) {
                    $table->unsignedBigInteger('entidad_contraparte_id')->nullable()->after('proyecto_id');
                }
                if (!Schema::hasColumn('entidad_contraparte_proyecto', 'rtn')) {
                    $table->string('rtn', 20)->nullable()->after('entidad_contraparte_id');
                }
            });

            // Add catalog FK if not present
            $fkExists = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'entidad_contraparte_proyecto'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            ));
            if (!$fkExists && Schema::hasColumn('entidad_contraparte_proyecto', 'entidad_contraparte_id')) {
                try {
                    Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
                        $table->foreign('entidad_contraparte_id', 'ecp_catalogo_fk')
                            ->references('id')->on('entidad_contraparte')->onDelete('set null');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // ─────────────────────────────────────────────────────────
        // 5. Re-point instrumento_formalizacion FK to pivot table
        // ─────────────────────────────────────────────────────────
        if (Schema::hasColumn('instrumento_formalizacion', 'entidad_contraparte_id')) {
            $ifmFkExists = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'instrumento_formalizacion'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte_proyecto'"
            ));
            if (!$ifmFkExists && Schema::hasTable('entidad_contraparte_proyecto')) {
                try {
                    Schema::table('instrumento_formalizacion', function (Blueprint $table) {
                        $table->foreign('entidad_contraparte_id', 'ifm_ecp_fk')
                            ->references('id')->on('entidad_contraparte_proyecto')->onDelete('cascade');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // ─────────────────────────────────────────────────────────
        // 6. Re-point informe_final_contrapartes FK to catalog
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('informe_final_contrapartes') && Schema::hasColumn('informe_final_contrapartes', 'entidad_contraparte_id')) {
            $infFkToPivot = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'informe_final_contrapartes'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte_proyecto'"
            ));
            $infFkToCatalog = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'informe_final_contrapartes'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            ));

            if ($infFkToPivot && !$infFkToCatalog) {
                // Drop FK pointing to pivot
                $fks = DB::select(
                    "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'informe_final_contrapartes'
                     AND REFERENCED_TABLE_NAME = 'entidad_contraparte_proyecto'"
                );
                foreach ($fks as $fk) {
                    try {
                        DB::statement("ALTER TABLE `informe_final_contrapartes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Throwable $e) {}
                }
            }

            if (!$infFkToCatalog) {
                try {
                    Schema::table('informe_final_contrapartes', function (Blueprint $table) {
                        $table->foreign('entidad_contraparte_id', 'inf_contraparte_entidad_fk')
                            ->references('id')->on('entidad_contraparte')->nullOnDelete();
                    });
                } catch (\Throwable $e) {}
            }
        }

        // ─────────────────────────────────────────────────────────
        // 7. Backfill catalog from pivot (only if rows need links)
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('entidad_contraparte_proyecto') && Schema::hasColumn('entidad_contraparte_proyecto', 'entidad_contraparte_id') && Schema::hasTable('entidad_contraparte')) {
            $needsBackfill = DB::table('entidad_contraparte_proyecto')
                ->whereNull('entidad_contraparte_id')
                ->exists();

            if ($needsBackfill) {
                $existentes = DB::table('entidad_contraparte_proyecto')
                    ->select('nombre', 'tipo_entidad', 'nombre_contacto', 'cargo_contacto', 'correo', 'telefono')
                    ->whereNull('entidad_contraparte_id')
                    ->distinct()
                    ->get();

                foreach ($existentes as $fila) {
                    $catalogoId = DB::table('entidad_contraparte')->insertGetId([
                        'nombre' => $fila->nombre,
                        'tipo_entidad' => $fila->tipo_entidad,
                        'nombre_contacto' => $fila->nombre_contacto,
                        'cargo_contacto' => $fila->cargo_contacto,
                        'correo' => $fila->correo,
                        'telefono' => $fila->telefono,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('entidad_contraparte_proyecto')
                        ->where('nombre', $fila->nombre)
                        ->whereNull('entidad_contraparte_id')
                        ->where(function ($q) use ($fila) {
                            if ($fila->correo !== null) {
                                $q->where('correo', $fila->correo);
                            } else {
                                $q->whereNull('correo');
                            }
                        })
                        ->update(['entidad_contraparte_id' => $catalogoId]);
                }
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $pivotTableExists = Schema::hasTable('entidad_contraparte_proyecto');

        // Drop FK from instrumento_formalizacion pointing to pivot
        if (Schema::hasColumn('instrumento_formalizacion', 'entidad_contraparte_id')) {
            $fks = DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'instrumento_formalizacion'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte_proyecto'"
            );
            foreach ($fks as $fk) {
                try {
                    DB::statement("ALTER TABLE `instrumento_formalizacion` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {}
            }
        }

        // Drop FK from informe_final_contrapartes pointing to catalog
        if (Schema::hasTable('informe_final_contrapartes') && Schema::hasColumn('informe_final_contrapartes', 'entidad_contraparte_id')) {
            $fks = DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'informe_final_contrapartes'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            );
            foreach ($fks as $fk) {
                try {
                    DB::statement("ALTER TABLE `informe_final_contrapartes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {}
            }
        }

        // Drop FK from pivot pointing to catalog
        if ($pivotTableExists && Schema::hasColumn('entidad_contraparte_proyecto', 'entidad_contraparte_id')) {
            $fks = DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'entidad_contraparte_proyecto'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            );
            foreach ($fks as $fk) {
                try {
                    DB::statement("ALTER TABLE `entidad_contraparte_proyecto` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {}
            }
        }

        // Drop catalog and rename pivot back
        Schema::dropIfExists('entidad_contraparte');
        if ($pivotTableExists) {
            Schema::rename('entidad_contraparte_proyecto', 'entidad_contraparte');
        }

        // Restore aporte and FK on restored old table
        if (Schema::hasTable('entidad_contraparte') && !Schema::hasColumn('entidad_contraparte', 'aporte')) {
            Schema::table('entidad_contraparte', function (Blueprint $table) {
                $table->string('aporte')->default('');
            });
        }

        // Re-point instrumento_formalizacion FK back to old table
        if (Schema::hasColumn('instrumento_formalizacion', 'entidad_contraparte_id') && Schema::hasTable('entidad_contraparte')) {
            $fkExists = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'instrumento_formalizacion'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            ));
            if (!$fkExists) {
                try {
                    Schema::table('instrumento_formalizacion', function (Blueprint $table) {
                        $table->foreign('entidad_contraparte_id', 'instrumento_form_entidad_contraparte_fk')
                            ->references('id')->on('entidad_contraparte');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // Re-point informe_final_contrapartes FK back to old table
        if (Schema::hasTable('informe_final_contrapartes') && Schema::hasColumn('informe_final_contrapartes', 'entidad_contraparte_id') && Schema::hasTable('entidad_contraparte')) {
            $fkExists = !empty(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'informe_final_contrapartes'
                 AND REFERENCED_TABLE_NAME = 'entidad_contraparte'"
            ));
            if (!$fkExists) {
                try {
                    Schema::table('informe_final_contrapartes', function (Blueprint $table) {
                        $table->foreign('entidad_contraparte_id', 'inf_contraparte_entidad_fk')
                            ->references('id')->on('entidad_contraparte')->nullOnDelete();
                    });
                } catch (\Throwable $e) {}
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};