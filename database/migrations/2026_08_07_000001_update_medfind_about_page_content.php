<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $now = now();
        $aboutId = DB::table('about_us_pages')->value('id');
        if (! $aboutId) {
            $aboutId = DB::table('about_us_pages')->insertGetId(['status' => true, 'mission_status' => true, 'vision_status' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $about = <<<'HTML'
<h2>About MedFind Solutions</h2>
<h4>Connecting Patients with Verified Healthcare Professionals</h4>
<p>MedFind Solutions is Kenya's trusted healthcare marketing and professional directory, created to connect patients with verified healthcare professionals and healthcare facilities across the country.</p>
<p>Our platform helps patients discover, compare, and connect with licensed medical specialists, clinics, diagnostic centres, pharmacies, laboratories, and other healthcare providers from one trusted platform.</p>
<p>MedFind Solutions does not offer medical consultations, diagnosis, treatment, or appointment services. Our role is to increase the online visibility of healthcare professionals and facilities while making it easier for patients to find verified healthcare providers with confidence.</p>
<p>Every listed healthcare professional and facility undergoes verification through the relevant professional regulatory or licensing body before receiving a verification badge. This promotes trust, credibility, and transparency for patients and providers.</p>
<h3>What We Do</h3>
<p>MedFind Solutions provides a trusted place for healthcare professionals and facilities to showcase their services and connect with patients.</p>
<ul><li>Search for verified healthcare professionals by specialty or profession.</li><li>Find verified clinics and healthcare facilities by category or service.</li><li>Compare providers using accurate, verified information.</li><li>Contact healthcare professionals and facilities directly.</li><li>Discover healthcare services from one trusted platform.</li></ul>
<h3>Why Choose MedFind Solutions?</h3>
<p><strong>Verified Healthcare Providers.</strong> Every listed provider is verified through the appropriate regulatory or licensing body before appearing on the platform.</p>
<p><strong>Trusted Healthcare Directory.</strong> We promote confidence and transparency for patients seeking healthcare services.</p>
<p><strong>Increased Professional Visibility.</strong> We help providers strengthen their online presence, reach more patients, and showcase their expertise.</p>
<p><strong>Easy Search Experience.</strong> Intuitive search and filtering tools make it simple to compare and connect with providers.</p>
<p><strong>Nationwide Reach.</strong> MedFind Solutions is designed to grow into Kenya's leading healthcare directory.</p>
<h3>Our Commitment</h3>
<p>At MedFind Solutions, we believe trust begins with verification. We are committed to maintaining a professional, transparent, and reliable platform that benefits healthcare providers and the communities they serve.</p>
<p>Whether you are a patient looking for a verified provider or a healthcare professional seeking greater visibility, MedFind Solutions helps you make meaningful connections through a trusted professional directory.</p>
HTML;
        $mission = '<h2>Our Mission</h2><p>To become Kenya\'s most trusted healthcare marketing platform by connecting patients with verified healthcare professionals and healthcare facilities while promoting professional visibility, credibility, and accessibility.</p>';
        $vision = '<h2>Our Vision</h2><p>To build Africa\'s leading healthcare directory, where every patient can confidently find verified healthcare providers and every healthcare professional has the opportunity to grow their digital presence.</p>';

        foreach (DB::table('languages')->pluck('code') as $code) {
            DB::table('about_us_page_translations')->updateOrInsert(
                ['about_us_page_id' => $aboutId, 'lang_code' => $code],
                ['about_description' => $about, 'mission_description' => $mission, 'vision_description' => $vision, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void {}
};
