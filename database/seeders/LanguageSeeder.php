<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        $languages = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'da', 'name' => 'Danish'],
            // Add more languages here
        ];

        foreach ($languages as $lang) {
            Language::create($lang);
        }
    }
}
