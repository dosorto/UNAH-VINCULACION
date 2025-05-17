<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla sugerencias
        Schema::create('ticket', function (Blueprint $table) {
            $table->id();          
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo_ticket', 
            [
                'Soporte Tecnico', 
                'Sugerencia',
                'Consulta General', 
                'Otro'
            ]);
            $table->text('asunto'); 
            $table->enum('estado', ['abierto', 'en proceso', 'cerrado', 'eliminado'])->default('abierto');
            $table->timestamp('fecha_creacion')->useCurrent(); 
            $table->timestamp('fecha_cierre')->nullable();     
            $table->timestamps();
        });

        // Tabla ticket_sugerencia
        Schema::create('ticket_sugerencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('ticket')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('mensaje');
            $table->timestamp('fecha_envio')->useCurrent();
            $table->enum('estado', ['abierto', 'en proceso', 'cerrado', 'eliminado'])->default('abierto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sugerencia');
        Schema::dropIfExists('ticket');
    }
};
