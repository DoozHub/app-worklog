<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorklogSeeder extends Seeder
{
    public function run()
    {
        // Clients
        $clientId1 = DB::table('worklog_clients')->insertGetId([
            'name' => 'Acme Corp',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clientId2 = DB::table('worklog_clients')->insertGetId([
            'name' => 'Internal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Projects
        DB::table('worklog_projects')->insert([
            'client_id' => $clientId1,
            'name' => 'Website Redesign',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('worklog_projects')->insert([
            'client_id' => $clientId1,
            'name' => 'API Integration',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('worklog_projects')->insert([
            'client_id' => $clientId2,
            'name' => 'Daily Standup',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Worklog seed data created.\n";
    }
}
