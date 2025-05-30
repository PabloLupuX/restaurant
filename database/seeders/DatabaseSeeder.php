<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            AlmacenSeeder::class,
            ClientTypeSeeder::class,
            EmployeeTypeSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
        ]);
    }
}
