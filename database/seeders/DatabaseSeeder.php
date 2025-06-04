<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Assessor;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Admin::create([
            'name' => 'Admin Master',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin123'),
        ]);
        
        Assessor::create([
            'name' => 'Asesor',
            'email' => 'asesor@asesor.com',
            'password' => bcrypt('asesor123'),
        ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
