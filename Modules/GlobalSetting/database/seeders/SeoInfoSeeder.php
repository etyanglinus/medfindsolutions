<?php

namespace Modules\GlobalSetting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\GlobalSetting\app\Models\SeoSetting;

class SeoInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item1 = new SeoSetting();
        $item1->page_name = 'Home';
        $item1->seo_title = 'Home || Medfind';
        $item1->seo_description = 'Home || Medfind';
        $item1->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'About';
        $item2->seo_title = 'About || Medfind';
        $item2->seo_description = 'About || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Contact';
        $item2->seo_title = 'Contact || Medfind';
        $item2->seo_description = 'Contact || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Blog';
        $item2->seo_title = 'Blog || Medfind';
        $item2->seo_description = 'Blog || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Doctor';
        $item2->seo_title = 'Doctor || Medfind';
        $item2->seo_description = 'Doctor || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Department';
        $item2->seo_title = 'Department || Medfind';
        $item2->seo_description = 'Department || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Service';
        $item2->seo_title = 'Service || Medfind';
        $item2->seo_description = 'Service || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Testimonial';
        $item2->seo_title = 'Testimonial || Medfind';
        $item2->seo_description = 'Testimonial || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'FaQ';
        $item2->seo_title = 'FaQ || Medfind';
        $item2->seo_description = 'FaQ || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Privacy Policy';
        $item2->seo_title = 'Privacy Policy || Medfind';
        $item2->seo_description = 'Privacy Policy || Medfind';
        $item2->save();

        $item2 = new SeoSetting();
        $item2->page_name = 'Terms Condition';
        $item2->seo_title = 'Terms and Condition || Medfind';
        $item2->seo_description = 'Terms and Condition || Medfind';
        $item2->save();
    }
}
