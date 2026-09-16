<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pps_documentos_generados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pps_servicio_social_id')->constrained('pps_servicio_social', 'id')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('archivo');
            $table->string('nombre_original');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('generado_por')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('generado_en')->nullable();
            $table->timestamps();
            $table->index(['pps_servicio_social_id', 'tipo'], 'pps_doc_gen_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pps_documentos_generados');
    }
};
