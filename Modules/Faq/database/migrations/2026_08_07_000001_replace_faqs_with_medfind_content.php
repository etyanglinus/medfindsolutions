<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $now = now();
        DB::table('faq_translations')->delete();
        DB::table('faqs')->delete();
        DB::table('faq_category_translations')->delete();
        DB::table('faq_categories')->delete();

        $categories = [
            'about-medfind' => ['About MedFind Solutions', [
                ['What is MedFind Solutions?', "MedFind Solutions is Kenya's trusted healthcare marketing platform and professional directory that connects patients with verified healthcare professionals and healthcare facilities."],
                ['Does MedFind Solutions provide medical treatment or consultations?', 'No. MedFind Solutions does not provide medical consultations, diagnosis, treatment, or emergency services. We serve as a platform that helps patients discover and connect with verified healthcare providers.'],
                ['Does MedFind Solutions guarantee the quality of healthcare services?', 'No. MedFind Solutions verifies professional registration and licensing status but does not guarantee the quality, outcomes, or services provided by individual healthcare professionals or healthcare facilities.'],
            ]],
            'registration-subscriptions' => ['Registration & Subscriptions', [
                ['Who can register on MedFind Solutions?', 'Licensed healthcare professionals, specialists, clinics, diagnostic centres, laboratories, pharmacies, and other eligible healthcare facilities can register and create a professional profile.'],
                ['Is there a fee to register?', 'Yes. MedFind Solutions offers subscription packages designed to help healthcare professionals and healthcare facilities increase their visibility and reach more patients. You can view all available packages on our Pricing page.'],
                ['Can I update my profile after registering?', 'Yes. Registered users can log in to their dashboard at any time to update their profile information, services, contact details, and subscription.'],
            ]],
            'verification' => ['Verification', [
                ['How are healthcare professionals verified?', 'Healthcare professionals are verified using their current practising licence and registration details issued by their respective professional regulatory or licensing body before receiving a Verified badge on the platform.'],
                ['How are healthcare facilities verified?', 'Healthcare facilities are verified using their valid operating licence issued by the appropriate healthcare regulatory authority before being listed as Verified.'],
                ['How do I become a Verified Provider?', 'Complete your registration, upload the required licence and supporting documents, and submit them for verification. Once approved, your profile will display the Verified badge.'],
            ]],
            'using-medfind' => ['Using MedFind Solutions', [
                ['Can patients contact healthcare providers directly?', 'Yes. Patients can use the contact information provided on each verified profile to communicate directly with the healthcare professional or healthcare facility.'],
                ['How can I contact MedFind Solutions?', 'You can reach us through the Contact Us page for enquiries about registrations, subscriptions, partnerships, or general support.'],
            ]],
        ];
        $languages = DB::table('languages')->pluck('code');
        foreach ($categories as $slug => [$title, $items]) {
            $categoryId = DB::table('faq_categories')->insertGetId(['slug' => $slug, 'status' => true, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($languages as $code) {
                DB::table('faq_category_translations')->insert(['faq_category_id' => $categoryId, 'lang_code' => $code, 'title' => $title, 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach ($items as [$question, $answer]) {
                $faqId = DB::table('faqs')->insertGetId(['faq_category_id' => $categoryId, 'status' => true, 'created_at' => $now, 'updated_at' => $now]);
                foreach ($languages as $code) {
                    DB::table('faq_translations')->insert(['faq_id' => $faqId, 'lang_code' => $code, 'question' => $question, 'answer' => $answer, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }
    }

    public function down(): void {}
};
