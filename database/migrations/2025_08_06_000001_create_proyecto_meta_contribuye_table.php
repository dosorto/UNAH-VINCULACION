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
        Schema::create('proyecto_meta_contribuye', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('meta_contribuye_id');
            $table->timestamps();

            $table->foreign('proyecto_id')->references('id')->on('proyecto')->onDelete('cascade');
            $table->foreign('meta_contribuye_id')->references('id')->on('metas_contribuye')->onDelete('cascade');
            
            $table->unique(['proyecto_id', 'meta_contribuye_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_meta_contribuye');
    }
};
