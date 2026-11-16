<?php

namespace Modules\SocialLink\database\seeders;

use Illuminate\Database\Seeder;
use Modules\SocialLink\app\Models\SocialLink;

class SocialLinkDatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        SocialLink::create([
            'icon' => 'uploads/website-images/dummy/instragram.svg',
            'link' => 'https://www.instagram.com',
        ]);
        SocialLink::create([
            'icon' => 'uploads/website-images/dummy/facebook.svg',
            'link' => 'https://www.facebook.com',
        ]);

        SocialLink::create([
            'icon' => 'uploads/website-images/dummy/linkedin.svg',
            'link' => 'https://www.linkedin.com',
        ]);
        SocialLink::create([
            'icon' => 'uploads/website-images/dummy/twitter.svg',
            'link' => 'https://www.twitter.com',
        ]);
    }
}