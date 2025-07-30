<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained();
            $table->string('tipo_integrante'); // empleado, estudiante, internacional
            $table->unsignedBigInteger('integrante_id');
            $table->date('fecha_baja');
            $table->string('motivo_baja');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('equipo_ejecutor_bajas');
    }
};
