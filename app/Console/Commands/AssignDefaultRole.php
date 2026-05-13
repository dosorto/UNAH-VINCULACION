<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

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
    protected $description = 'Report users without roles and clear invalid active roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking users without roles...');
        
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        
        if ($usersWithoutRoles->isEmpty()) {
            $this->info('All users already have roles assigned.');
            return 0;
        }
        
        $count = 0;
        foreach ($usersWithoutRoles as $user) {
            if ($user->active_role_id !== null) {
                $user->active_role_id = null;
                $user->save();
            }
            
            $count++;
            $this->line("User without roles: {$user->name} ({$user->email})");
        }
        
        $this->warn("No roles were assigned automatically. Review {$count} users manually.");
        
        return 0;
    }
}
