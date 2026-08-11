<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        DB::table('proyecto')
            ->whereNotNull('descripcion_participantes')
            ->whereRaw("TRIM(descripcion_participantes) <> ''")
            ->where(function ($query): void {
                $query->whereNull('participacion_unah')
                    ->orWhereRaw("TRIM(participacion_unah) = ''");
            })
            ->update([
                'participacion_unah' => DB::raw('descripcion_participantes'),
            ]);
    }

    public function down(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        DB::table('proyecto')
            ->whereNotNull('descripcion_participantes')
            ->whereColumn('participacion_unah', 'descripcion_participantes')
            ->update(['participacion_unah' => null]);
    }

    private function hasRequiredSchema(): bool
    {
        return Schema::hasTable('proyecto')
            && Schema::hasColumn('proyecto', 'descripcion_participantes')
            && Schema::hasColumn('proyecto', 'participacion_unah');
    }
};
