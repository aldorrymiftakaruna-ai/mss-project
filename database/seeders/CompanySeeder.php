<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::insert([
            ['code' => 'CES', 'name' => 'Citra Enggal Sejahtera', 'alias' => 'PT. Alpha', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'NPA', 'name' => 'Nutripro Prima Asia', 'alias' => 'PT. Beta', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}