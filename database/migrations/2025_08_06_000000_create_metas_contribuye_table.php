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
        Schema::create('metas_contribuye', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ods_id');
            $table->string('numero_meta');
            $table->text('descripcion');
            $table->timestamps();

            $table->foreign('ods_id')->references('id')->on('ods')->onDelete('cascade');
            $table->index('ods_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas_contribuye');
    }
};
