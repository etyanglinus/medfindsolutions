<?php

namespace Modules\Doctor\app\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Doctor\app\Models\SubscriptionPackage;

/**
 * Single source of truth for subscription pricing, duration and entitlements.
 * Controllers must resolve plans here; prices and privileges are never accepted
 * from a browser request.
 */
class SubscriptionPlanService
{
    public function for(string $provider): array
    {
        if (Schema::hasTable('subscription_packages')) {
            $packages = SubscriptionPackage::query()->where('provider_type', $provider)->where('is_active', true)->orderBy('price')->get();
            if ($packages->isNotEmpty()) {
                return $packages->map(fn (SubscriptionPackage $plan) => [
                    'id' => $plan->code, 'name' => $plan->name, 'tagline' => $plan->tagline,
                    'price' => (float) $plan->price, 'duration' => trans_choice(':count Month|:count Months', $plan->duration_months, ['count' => $plan->duration_months]),
                    'duration_months' => $plan->duration_months, 'features' => $this->featuresFor($provider, $plan->entitlements ?? []), 'entitlements' => $this->supportedEntitlements($provider, $plan->entitlements ?? []),
                ])->all();
            }
        }

        return $this->defaults($provider);
    }

    public function defaults(string $provider): array
    {
        $plans = match ($provider) {
            'clinic' => $this->clinicPlans(),
            'school' => $this->schoolPlans(),
            'supplier' => $this->supplierPlans(),
            default => $this->doctorPlans(),
        };

        return array_map(function (array $plan) use ($provider) {
            $plan['entitlements'] = $this->supportedEntitlements($provider, $plan['entitlements']);
            $plan['features'] = $this->featuresFor($provider, $plan['entitlements']);
            return $plan;
        }, $plans);
    }

    public function find(string $provider, string $code): ?array
    {
        foreach ($this->for($provider) as $plan) {
            if ($plan['id'] === $code) return $plan;
        }

        return null;
    }

    public function supportedEntitlements(string $provider, array $entitlements): array
    {
        $supported = match ($provider) {
            'clinic' => ['search_visibility', 'contact', 'whatsapp', 'description', 'insurance', 'maps', 'top_clinic_badge', 'homepage_rotation', 'services_limit', 'highlights_limit', 'cover_photo', 'gallery_limit', 'equipment', 'reviews', 'emergency', 'operating_hours', 'branches', 'featured_services', 'inquiries', 'analytics'],
            'school' => ['search_visibility', 'verified_badge', 'homepage_rotation', 'courses_limit', 'featured_courses', 'intake_promotion', 'analytics', 'inquiries', 'institution_profile', 'photos', 'whatsapp', 'website', 'location_map', 'application_information', 'campus_facilities', 'practical_training', 'accommodation', 'international_pathways', 'featured_intake_campaign', 'promotional_banner'],
            'supplier' => ['search_visibility', 'verified_badge', 'homepage_rotation', 'products_limit', 'catalogue', 'gallery', 'gallery_limit', 'services', 'analytics', 'inquiries', 'supplier_profile', 'categories', 'supplier_types', 'product_images', 'additional_images_limit', 'whatsapp', 'website', 'areas_served', 'brands', 'social_links', 'featured_products', 'featured_supplier', 'promotional_banner'],
            default => ['search_visibility', 'contact', 'verified_badge', 'whatsapp', 'homepage_rotation', 'consultation_fee', 'services', 'analytics', 'insurance', 'telemedicine', 'emergency', 'reviews', 'inquiries', 'top_specialist_badge'],
        };

        return array_intersect_key($entitlements, array_flip($supported));
    }

    private function featuresFor(string $provider, array $entitlements): array
    {
        $entitlements = $this->supportedEntitlements($provider, $entitlements);
        $features = match ($provider) {
            'clinic' => [__('Facility listing'), __('Facility details and contact information')],
            'school' => [__('Institution profile'), __('Healthcare course discovery')],
            'supplier' => [__('Supplier profile'), __('Healthcare product discovery'), __('Request quotation')],
            default => [__('Specialist profile'), __('Search visibility'), __('Call and email contact')],
        };
        $labels = match ($provider) {
            'school' => ['photos' => __('Logo and profile photos'), 'whatsapp' => __('WhatsApp contact'), 'website' => __('Website link'), 'location_map' => __('Location and map'), 'application_information' => __('Application information'), 'campus_facilities' => __('Campus facilities'), 'practical_training' => __('Clinical and practical training'), 'accommodation' => __('Accommodation information'), 'international_pathways' => __('International career pathways'), 'featured_courses' => __('Featured courses'), 'intake_promotion' => __('Intake promotion'), 'analytics' => __('Lead and enquiry insights'), 'homepage_rotation' => __('Homepage exposure'), 'featured_intake_campaign' => __('Featured intake campaigns'), 'promotional_banner' => __('Promotional banner')],
            'supplier' => ['supplier_types' => __('Supplier specializations'), 'product_images' => __('Product images'), 'additional_images_limit' => __('Additional product images per product'), 'whatsapp' => __('WhatsApp contact'), 'website' => __('Website link'), 'areas_served' => __('Areas served'), 'brands' => __('Brands supplied'), 'catalogue' => __('Public catalogues and brochures'), 'gallery' => __('Supplier gallery'), 'gallery_limit' => __('Supplier gallery image limit'), 'services' => __('Supplier services'), 'social_links' => __('Social links'), 'analytics' => __('Supplier and product insights'), 'homepage_rotation' => __('Homepage exposure'), 'featured_products' => __('Featured products'), 'featured_supplier' => __('Featured supplier placement'), 'promotional_banner' => __('Promotional banner')],
            'clinic' => ['whatsapp' => __('WhatsApp contact'), 'description' => __('Detailed clinic description'), 'insurance' => __('Insurance accepted'), 'maps' => __('Google Maps link'), 'gallery_limit' => __('Gallery images'), 'equipment' => __('Equipment and technology'), 'reviews' => __('Provider reviews'), 'emergency' => __('Emergency availability'), 'operating_hours' => __('Operating hours'), 'branches' => __('Additional branch locations'), 'featured_services' => __('Featured services'), 'inquiries' => __('Facility enquiries'), 'analytics' => __('Profile analytics'), 'top_clinic_badge' => __('Featured facility placement')],
            default => ['verified_badge' => __('Verified profile badge'), 'whatsapp' => __('WhatsApp contact'), 'consultation_fee' => __('Consultation fee'), 'services' => __('Services offered'), 'analytics' => __('Profile analytics'), 'insurance' => __('Insurance accepted'), 'telemedicine' => __('Telemedicine availability'), 'emergency' => __('Emergency availability'), 'reviews' => __('Patient reviews'), 'inquiries' => __('Professional enquiries'), 'homepage_rotation' => __('Homepage featured placement'), 'top_specialist_badge' => __('Top specialist badge')],
        };

        foreach ($labels as $key => $label) {
            if (data_get($entitlements, $key)) {
                $features[] = $label;
            }
        }

        return $features;
    }

    private function doctorPlans(): array
    {
        return [
            ['id' => 'basic', 'name' => __('Basic'), 'tagline' => __('Essential visibility'), 'price' => 1000, 'duration' => __('1 Month'), 'duration_months' => 1,
             'features' => [__('Profile listing'), __('Search visibility'), __('Call / email contact')],
             'entitlements' => ['search_visibility' => true, 'contact' => true]],
            ['id' => 'premium', 'name' => __('Premium'), 'tagline' => __('Grow your practice'), 'price' => 3500, 'duration' => __('3 Months'), 'duration_months' => 3,
             'features' => [__('Everything in Basic'), __('Verified badge'), __('WhatsApp button')],
             'entitlements' => ['search_visibility' => true, 'contact' => true, 'verified_badge' => true, 'whatsapp' => true, 'consultation_fee' => true, 'services' => true, 'analytics' => true, 'inquiries' => true]],
            ['id' => 'executive', 'name' => __('Executive'), 'tagline' => __('Maximum visibility'), 'price' => 10000, 'duration' => __('6 Months'), 'duration_months' => 6,
             'features' => [__('Everything in Premium'), __('Homepage featured placement')],
             'entitlements' => ['search_visibility' => true, 'contact' => true, 'verified_badge' => true, 'whatsapp' => true, 'consultation_fee' => true, 'services' => true, 'analytics' => true, 'inquiries' => true, 'insurance' => true, 'telemedicine' => true, 'emergency' => true, 'reviews' => true, 'homepage_rotation' => true, 'top_specialist_badge' => true]],
        ];
    }

    private function clinicPlans(): array
    {
        return [
            ['id' => 'basic', 'name' => __('Basic'), 'tagline' => __('Essential listing'), 'price' => 3000, 'duration' => __('1 Month'), 'duration_months' => 1,
             'features' => [__('Clinic/Centre name'), __('Specialty'), __('Clinic logo'), __('Phone number')],
             'entitlements' => ['search_visibility' => true, 'contact' => true]],
            ['id' => 'premium', 'name' => __('Premium'), 'tagline' => __('More patient enquiries'), 'price' => 10000, 'duration' => __('3 Months'), 'duration_months' => 3,
             'features' => [__('Everything in Basic'), __('WhatsApp number'), __('Detailed clinic description'), __('Insurance accepted'), __('Google Maps link')],
             'entitlements' => ['search_visibility' => true, 'contact' => true, 'whatsapp' => true, 'description' => true, 'insurance' => true, 'maps' => true, 'services_limit' => 10, 'highlights_limit' => 5, 'inquiries' => true, 'analytics' => true]],
            ['id' => 'executive', 'name' => __('Executive'), 'tagline' => __('Complete facility presence'), 'price' => 15000, 'duration' => __('6 Months'), 'duration_months' => 6,
             'features' => [__('Everything in Premium'), __('Up to 15 gallery photos'), __('Up to 20 services offered'), __('Equipment & technology available'), __('Insurance accepted'), __('Patient reviews & ratings (enable/disable)'), __('24-hour emergency services'), __('Emergency appointments available'), __('Emergency contact available'), __('Operating hours'), __('Google Maps link'), __('Additional branch locations'), __('Featured services'), __('⭐ Top Clinic badge')],
             'entitlements' => ['search_visibility' => true, 'contact' => true, 'services_limit' => 20, 'highlights_limit' => 5, 'cover_photo' => true, 'whatsapp' => true, 'description' => true, 'insurance' => true, 'gallery_limit' => 15, 'equipment' => true, 'reviews' => true, 'emergency' => true, 'operating_hours' => true, 'maps' => true, 'branches' => true, 'featured_services' => true, 'inquiries' => true, 'analytics' => true, 'homepage_rotation' => true, 'top_clinic_badge' => true]],
        ];
    }

    private function schoolPlans(): array
    {
        return [
            ['id' => 'standard', 'name' => __('Standard'), 'tagline' => __('Start being discovered'), 'price' => 3000, 'duration' => __('1 Month'), 'duration_months' => 1, 'features' => [__('Institution profile'), __('Up to 5 courses'), __('Intake and contact information')], 'entitlements' => ['search_visibility' => true, 'institution_profile' => true, 'photos' => true, 'courses_limit' => 5, 'inquiries' => true, 'whatsapp' => true, 'website' => true, 'location_map' => true, 'application_information' => true, 'campus_facilities' => true, 'practical_training' => true, 'accommodation' => true]],
            ['id' => 'premium', 'name' => __('Premium'), 'tagline' => __('Reach more students'), 'price' => 5000, 'duration' => __('3 Months'), 'duration_months' => 3, 'features' => [__('Everything in Standard'), __('Up to 15 courses'), __('Featured courses and intake promotion')], 'entitlements' => ['search_visibility' => true, 'institution_profile' => true, 'photos' => true, 'whatsapp' => true, 'website' => true, 'location_map' => true, 'application_information' => true, 'campus_facilities' => true, 'practical_training' => true, 'accommodation' => true, 'courses_limit' => 15, 'featured_courses' => true, 'intake_promotion' => true, 'international_pathways' => true, 'analytics' => true, 'inquiries' => true]],
            ['id' => 'executive', 'name' => __('Executive'), 'tagline' => __('Maximum institution visibility'), 'price' => 8000, 'duration' => __('6 Months'), 'duration_months' => 6, 'features' => [__('Everything in Premium'), __('Unlimited courses'), __('Homepage and featured institution exposure')], 'entitlements' => ['search_visibility' => true, 'institution_profile' => true, 'photos' => true, 'whatsapp' => true, 'website' => true, 'location_map' => true, 'application_information' => true, 'campus_facilities' => true, 'practical_training' => true, 'accommodation' => true, 'international_pathways' => true, 'homepage_rotation' => true, 'courses_limit' => 0, 'featured_courses' => true, 'intake_promotion' => true, 'featured_intake_campaign' => true, 'promotional_banner' => true, 'analytics' => true, 'inquiries' => true]],
        ];
    }

    private function supplierPlans(): array
    {
        return [
            ['id' => 'standard', 'name' => __('Standard'), 'tagline' => __('Start being discovered'), 'price' => 3000, 'duration' => __('1 Month'), 'duration_months' => 1, 'features' => [__('Supplier profile'), __('Up to 10 product listings'), __('Request quotation button')], 'entitlements' => ['search_visibility' => true, 'supplier_profile' => true, 'supplier_types' => true, 'categories' => true, 'product_images' => true, 'additional_images_limit' => 0, 'products_limit' => 10, 'inquiries' => true, 'whatsapp' => true, 'website' => true, 'areas_served' => true]],
            ['id' => 'premium', 'name' => __('Premium'), 'tagline' => __('Showcase more capability'), 'price' => 5000, 'duration' => __('3 Months'), 'duration_months' => 3, 'features' => [__('Everything in Standard'), __('Up to 50 products'), __('Brands, catalogue, gallery and services')], 'entitlements' => ['search_visibility' => true, 'supplier_profile' => true, 'supplier_types' => true, 'categories' => true, 'product_images' => true, 'additional_images_limit' => 5, 'products_limit' => 50, 'catalogue' => true, 'gallery' => true, 'gallery_limit' => 8, 'services' => true, 'brands' => true, 'social_links' => true, 'analytics' => true, 'inquiries' => true, 'whatsapp' => true, 'website' => true, 'areas_served' => true]],
            ['id' => 'executive', 'name' => __('Executive'), 'tagline' => __('Maximum supplier visibility'), 'price' => 8000, 'duration' => __('6 Months'), 'duration_months' => 6, 'features' => [__('Everything in Premium'), __('Unlimited products'), __('Featured supplier and homepage exposure')], 'entitlements' => ['search_visibility' => true, 'supplier_profile' => true, 'supplier_types' => true, 'categories' => true, 'product_images' => true, 'additional_images_limit' => 10, 'homepage_rotation' => true, 'products_limit' => 0, 'catalogue' => true, 'gallery' => true, 'gallery_limit' => 16, 'services' => true, 'brands' => true, 'social_links' => true, 'featured_products' => true, 'featured_supplier' => true, 'promotional_banner' => true, 'analytics' => true, 'inquiries' => true, 'whatsapp' => true, 'website' => true, 'areas_served' => true]],
        ];
    }
}
