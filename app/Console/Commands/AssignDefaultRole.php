<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignDefaultRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-default-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign default "docente" role to users without any role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking users without roles...');
        
        $docenteRole = Role::where('name', 'docente')->first();
        
        if (!$docenteRole) {
            $this->error('Role "docente" not found. Please create it first.');
            return 1;
        }
        
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        
        if ($usersWithoutRoles->isEmpty()) {
            $this->info('All users already have roles assigned.');
            return 0;
        }
        
        $count = 0;
        foreach ($usersWithoutRoles as $user) {
            $user->assignRole('docente');
            
            // Asignar active_role_id si no lo tiene
            if (!$user->active_role_id) {
                $user->active_role_id = $docenteRole->id;
                $user->save();
            }
            
            $count++;
            $this->line("✓ Assigned 'docente' role to user: {$user->name} ({$user->email})");
        }
        
        $this->info("Successfully assigned 'docente' role to {$count} users.");
        
        return 0;
    }
}
