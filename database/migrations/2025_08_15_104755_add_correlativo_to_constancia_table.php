<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('constancia', function (Blueprint $table) {
            $table->string('correlativo', 50)->unique()->nullable()->after('hash');
        });
    }

    public function down()
    {
        Schema::table('constancia', function (Blueprint $table) {
            $table->dropColumn('correlativo');
        });
    }
};