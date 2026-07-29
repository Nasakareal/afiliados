<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LonasUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DistrictCoordinatorUsersSeeder::class);
    }
}
