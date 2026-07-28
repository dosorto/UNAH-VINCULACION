<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->string('rtn', 14)->nullable()->after('documento_identidad');
            $table->index('rtn');
        });
    }

    public function down(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->dropIndex(['rtn']);
            $table->dropColumn('rtn');
        });
    }
};
