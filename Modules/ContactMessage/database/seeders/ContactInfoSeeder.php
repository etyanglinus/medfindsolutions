<?php

namespace Modules\ContactMessage\database\seeders;

use Illuminate\Database\Seeder;
use Modules\ContactMessage\app\Models\ContactInfo;
use Modules\ContactMessage\app\Models\ContactInfoTranslation;

class ContactInfoSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $requestOne = (object) [
            'top_bar_email'  => 'info@medfindsolutions.co.ke',
            'top_bar_phone'  => '+254117295891',
            'email'          => 'info@medfindsolutions.co.ke',
            'phone'          => '+254117295891',
            'address'        => 'Kenya',
            'map_embed_code' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3026.929848957016!2d-73.65008138515348!3d40.65347674913173!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c27b4c1cf34df7%3A0x83ce632b88556b58!2zOTUgUyBQYXJrIEF2ZSwgUm9ja3ZpbGxlIENlbnRyZSwgTlkgMTE1NzAsIOCmruCmvuCmsOCnjeCmleCmv-CmqCDgpq_gp4HgppXgp43gpqTgprDgpr7gprfgp43gpp_gp43gprA!5e0!3m2!1sbn!2sbd!4v1626145586281!5m2!1sbn!2sbd" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
        ];

        $translations = [
            [
                'lang_code'   => 'en',
                'header'      => 'Contact Us',
                'description' => 'Please fill in the following form to contact us quickly.',
                'about'       => 'We provide top-notch medical care and easy appointment scheduling. Our skilled healthcare team ensures personalized, compassionate treatment. Book your appointment today for exceptional care.',
                'copyright'   => 'Copyright 2024, Websolutionus. All Rights Reserved.',
            ],
            [
                'lang_code'   => 'ar',
                'header'      => 'اتصل بنا',
                'description' => 'يرجى ملء النموذج التالي للتواصل معنا بسرعة.',
                'about'       => 'نقدم رعاية طبية عالية الجودة وتحديد مواعيد بسهولة. فريقنا الطبي الماهر يضمن لك العلاج المخصص والرحيم. احجز موعدك اليوم لتجربة رعاية استثنائية.',
                'copyright'   => 'حقوق النشر 2024، Websolutionus. جميع الحقوق محفوظة.',
            ],
        ];

        $contactPage = new ContactInfo();
        $contactPage->fill((array) $requestOne);
        $contactPage->save();

        foreach ($translations as $translation) {
            $translationModel = new ContactInfoTranslation();
            $translationModel->lang_code = $translation['lang_code'];
            $translationModel->contact_info_id = $contactPage->id;
            $translationModel->header = $translation['header'];
            $translationModel->description = $translation['description'];
            $translationModel->about = $translation['about'];
            $translationModel->copyright = $translation['copyright'];
            $translationModel->save();
        }
    }
}
