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

        Schema::create('tipos_constancia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');         // Ej: Constancia de participación
            $table->text('descripcion')->nullable(); // Opcional
            $table->timestamps();
        });


        Schema::create('constancia', function (Blueprint $table) {
            $table->id();

            // Polimorfismo: modelo de origen (motivo de la constancia)
            $table->morphs('origen'); // equivale a origen_type y origen_id

            // Polimorfismo: modelo destinatario (a quien se le genera la constancia)
            $table->string('destinatario_type');
            $table->unsignedBigInteger('destinatario_id');

            // Relación con tipo de constancia
            $table->foreignId('tipo_constancia_id')->constrained('tipos_constancia');

            $table->string('hash', 25)->unique();


            // Status y validaciones
            $table->boolean('status')->default(true);
            $table->unsignedInteger('validaciones')->default(0);

            $table->timestamps();
        });


        Schema::create('descarga_constancia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constancia_id')->constrained('constancia')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('descargado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancia');
    }
};
