<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $now=now(); DB::table('permissions')->updateOrInsert(['name'=>'configuracion.integraciones-api','guard_name'=>'web'],['display_name'=>'Administrar Integraciones API','updated_at'=>$now,'created_at'=>$now]);
        $permissionId=DB::table('permissions')->where('name','configuracion.integraciones-api')->where('guard_name','web')->value('id');
        $adminId=DB::table('roles')->where('name','admin')->where('guard_name','web')->value('id');
        if ($permissionId && $adminId) DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$permissionId,'role_id'=>$adminId]);
    }
    public function down(): void { DB::table('permissions')->where('name','configuracion.integraciones-api')->where('guard_name','web')->delete(); }
};
