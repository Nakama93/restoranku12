<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories =[
            ['cat_name'=>'Makanan','description'=>'Kategori Makanan'],
            ['cat_name'=>'Minuman','description'=>'Kategori Minuman'],
        ];

        DB::table('categories')->insert($categories);
    }
}
