<?php

namespace Database\Seeders;

use Kohana\Database\DB;
use Kohana\Database\Seeder;

class MigAuthorSeeder extends Seeder
{
    public function run(): void
    {
        DB::insert('mig_authors', ['name'])->values(['Ayşe'])->values(['Ali'])->execute();

        $this->call(MigPostSeeder::class);
    }
}
