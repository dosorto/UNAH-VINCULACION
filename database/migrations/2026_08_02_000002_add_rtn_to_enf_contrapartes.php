<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_contrapartes', function (Blueprint $table): void {
            if (! Schema::hasColumn('enf_contrapartes', 'rtn')) {
                $table->string('rtn', 50)->nullable()->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enf_contrapartes', function (Blueprint $table): void {
            if (Schema::hasColumn('enf_contrapartes', 'rtn')) {
                $table->dropColumn('rtn');
            }
        });
    }
};
