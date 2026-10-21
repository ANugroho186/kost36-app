<?php

namespace Database\Seeders;

use App\Models\User;
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
        if (app()->isLocal()) {
            User::factory()->owner()->create([
                'name' => 'Pemilik Kost',
                'username' => 'pemilik',
                'email' => 'pemilik@example.com',
            ]);
        }
    }
}
