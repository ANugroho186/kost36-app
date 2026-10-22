<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun dengan password yang diketahui umum hanya boleh ada di mesin lokal.
        // Instance demo nanti menjalankan DemoSeeder secara eksplisit.
        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
