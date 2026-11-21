<?php

namespace App\Database\Seeders;

use Kohana\Database\DB;
use Kohana\Database\Seeder;

class MigPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = DB::select('id')->from('mig_authors')->where('name', '=', 'Ayşe')->execute()->get('id');

        DB::insert('mig_posts', ['mig_author_id', 'title', 'published'])->values([$author, 'Merhaba', true])->execute();
    }
}
