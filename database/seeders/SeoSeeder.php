<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seo;

class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page' => 'home',
                'title' => 'Welcome to Our Homepage',
                'description' => 'This is the description for the homepage.',
                'og_title' => 'Home OG Title',
                'og_description' => 'Homepage OG description',
                'keywords' => 'home, welcome, laravel'
            ],
            [
                'page' => 'about',
                'title' => 'About Us',
                'description' => 'Learn more about our company.',
                'og_title' => 'About OG Title',
                'og_description' => 'About OG description',
                'keywords' => 'about, company, info'
            ],
            [
                'page' => 'contact',
                'title' => 'Contact Us',
                'description' => 'Get in touch with us.',
                'og_title' => 'Contact OG Title',
                'og_description' => 'Contact OG description',
                'keywords' => 'contact, email, phone'
            ],
        ];

        foreach ($pages as $page) {
            Seo::create($page);
        }
    }
}
