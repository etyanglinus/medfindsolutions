<?php

namespace App\Services;

use App\Models\ListingEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\ProviderInquiry;
use Modules\Doctor\app\Services\SubscriptionPlanService;

/**
 * Everything the provider dashboards show, for all four types (specialist, facility, school,
 * supplier), driven by the plan the provider paid for: figures, chart, profile checklist,
 * shortcuts and the plan's contents only include what that package unlocks, and features of
 * higher packages appear as "upgrade to unlock". One design, rendered by provider.dashboard.overview.
 */
class ProviderDashboard
{
    public const TYPES = ['doctor', 'clinic', 'school', 'supplier'];

    public function build(string $type, Model $client, int $days = 30): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $insights = app(ListingInsightsService::class)->summary($type, $client, $days);
        $benefits = (array) ($insights['benefits'] ?? []);
        $has = fn (string $key) => !empty($benefits[$key]) || (str_ends_with($key, '_limit') && array_key_exists($key, $benefits) && is_numeric($benefits[$key]));
        $subscription = $insights['subscription'];
        $routes = $this->routes($type);

        // Figures for this period and the one before it.
        $previous = ListingEvent::where('provider_type', $type)->where('provider_id', $client->getKey())
            ->whereBetween('day', [now()->subDays(2 * $days - 1)->toDateString(), now()->subDays($days)->toDateString()])
            ->selectRaw('event, COUNT(*) as total')->groupBy('event')->pluck('total', 'event');
        $contacts = fn ($set) => (int) (($set['phone_click'] ?? 0) + ($set['whatsapp_click'] ?? 0) + ($set['website_click'] ?? 0));
        $inquiryQuery = $this->inquiryQuery($type, $client);
        $since = now()->subDays($days - 1)->startOfDay();
        $enquiryLabel = ['school' => __('Admission enquiries'), 'supplier' => __('Quotation requests')][$type] ?? __('Enquiries');
        $kpis = [
            ['label' => __('Profile views'), 'icon' => 'fa-eye', 'tone' => 'blue', 'now' => $insights['totals']['profile_view'] ?? 0, 'before' => (int) ($previous['profile_view'] ?? 0)],
            ['label' => __('Search appearances'), 'icon' => 'fa-search', 'tone' => 'violet', 'now' => $insights['totals']['search_appearance'] ?? 0, 'before' => (int) ($previous['search_appearance'] ?? 0)],
            ['label' => __('Calls, WhatsApp & website clicks'), 'icon' => 'fa-phone-alt', 'tone' => 'green', 'now' => $contacts($insights['totals']), 'before' => $contacts($previous)],
            $has('inquiries') && $inquiryQuery
                ? ['label' => $enquiryLabel, 'icon' => 'fa-envelope-open-text', 'tone' => 'amber', 'now' => (clone $inquiryQuery)->where('created_at', '>=', $since)->count(),
                    'before' => (clone $inquiryQuery)->whereBetween('created_at', [now()->subDays(2 * $days - 1)->startOfDay(), $since])->count()]
                : ['label' => $enquiryLabel, 'icon' => 'fa-lock', 'tone' => 'grey', 'locked' => true],
        ];
        $chart = [
            'labels' => collect($insights['labels'])->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
            'views' => $insights['series']['profile_view'] ?? [],
            'contacts' => collect($insights['series']['phone_click'] ?? [])->map(fn ($v, $i) => $v + ($insights['series']['whatsapp_click'][$i] ?? 0) + ($insights['series']['website_click'][$i] ?? 0))->values(),
        ];

        $recent = $has('inquiries') && $inquiryQuery ? (clone $inquiryQuery)->latest()->take(4)->get()->map(fn ($row) => $this->inquiryRow($type, $row)) : collect();
        $newCount = $has('inquiries') && $inquiryQuery ? (clone $inquiryQuery)->where('status', 'new')->count() : 0;

        $checklist = array_values(array_filter($this->checklist($type, $client, $has, $routes)));
        $strength = $checklist ? (int) round(100 * collect($checklist)->where('done', true)->count() / count($checklist)) : 100;
        $listing = app(PublicDirectory::class)->status($type, $client);

        $start = $subscription?->starts_at ?? $subscription?->approved_at ?? null;
        $daysLeft = $subscription?->expires_at ? max(0, (int) ceil(now()->diffInHours($subscription->expires_at, false) / 24)) : null;
        $planDays = $start && $subscription?->expires_at ? max(1, $start->diffInDays($subscription->expires_at)) : null;

        return [
            'type' => $type, 'days' => $days, 'name' => $client->name, 'subtitle' => $this->subtitle($type, $client), 'logo' => $this->logo($type, $client),
            'verified' => app(LicenceVerification::class)->isVerified($type, $client->getKey()),
            'listing' => $listing, 'subscription' => $subscription, 'planName' => $subscription?->package_name, 'daysLeft' => $daysLeft, 'planDays' => $planDays,
            'pending' => $this->pendingPayment($type, $client),
            'kpis' => $kpis, 'analytics' => $has('analytics'), 'chart' => $chart,
            'inquiriesEnabled' => $has('inquiries'), 'enquiryLabel' => $enquiryLabel, 'recent' => $recent, 'newCount' => $newCount,
            'checklist' => $checklist, 'strength' => $strength,
            'actions' => array_values(array_filter($this->actions($type, $has, $routes))),
            'included' => $this->included($type, $benefits), 'locked' => $this->locked($type, $benefits), 'meters' => $this->meters($type, $client, $benefits, $insights['usage'] ?? []),
            'routes' => $routes,
        ];
    }

    private function routes(string $type): array
    {
        return match ($type) {
            'doctor' => ['dashboard' => route('doctor.dashboard'), 'profile' => route('doctor.edit-profile', ['code' => function_exists('getSessionLanguage') ? getSessionLanguage() : 'en']), 'documents' => route('doctor.documents.index'),
                'inquiries' => route('doctor.inquiries.index'), 'messages' => route('doctor.admin-messages.index'), 'plans' => route('listing.doctor-plans'), 'receipts' => route('listing.receipts', ['type' => 'doctor']),
                'schedule' => route('doctor.schedule'), 'social' => route('doctor.social-link.index'), 'security' => route('doctor.change-password')],
            'clinic' => ['dashboard' => route('clinic.dashboard'), 'profile' => route('clinic.edit-profile'), 'documents' => route('clinic.documents.index'), 'inquiries' => route('clinic.inquiries.index'),
                'messages' => route('clinic.admin-messages.index'), 'plans' => route('clinic.subscription.status'), 'receipts' => route('listing.receipts', ['type' => 'clinic']), 'gallery' => route('clinic.content.index'),
                'security' => route('clinic.change-password')],
            default => ['dashboard' => route('website.partner.dashboard', ['type' => $type]), 'profile' => route('website.partner.profile', ['type' => $type]), 'documents' => route('website.partner.documents.index', ['type' => $type]),
                'inquiries' => $type === 'supplier' ? route('website.partner.quotations', ['type' => $type]) : route('website.partner.dashboard', ['type' => $type]).'#school-enquiries',
                'messages' => route('website.partner.admin-messages.index', ['type' => $type]), 'plans' => route('website.partner.subscription.renew', ['type' => $type]), 'receipts' => route('listing.receipts', ['type' => $type]),
                'items' => route('website.partner.dashboard', ['type' => $type]).'#items', 'security' => route('website.partner.account', ['type' => $type])],
        };
    }

    private function inquiryQuery(string $type, Model $client)
    {
        return match ($type) {
            'doctor', 'clinic' => ProviderInquiry::where('provider_type', $type)->where('provider_id', $client->getKey()),
            'school' => $client->enquiries(),
            'supplier' => $client->quotationRequests()->with('product'),
        };
    }

    private function inquiryRow(string $type, $row): array
    {
        return match ($type) {
            'doctor', 'clinic' => ['name' => $row->patient_name, 'text' => $row->reason, 'phone' => $row->phone, 'status' => $row->status, 'at' => $row->created_at],
            'school' => ['name' => $row->name, 'text' => trim(($row->course?->name ? $row->course->name.': ' : '').($row->message ?? '')), 'phone' => $row->phone ?? null, 'status' => $row->status, 'at' => $row->created_at],
            'supplier' => ['name' => $row->name, 'text' => trim(($row->product?->name ? $row->product->name.': ' : '').($row->message ?? '')), 'phone' => $row->phone ?? null, 'status' => $row->status, 'at' => $row->created_at],
        };
    }

    private function checklist(string $type, Model $client, \Closure $has, array $routes): array
    {
        $text = fn ($v) => mb_strlen(trim(strip_tags((string) $v)));
        $licence = ['done' => app(LicenceVerification::class)->hasValidLicence($type, $client->getKey()), 'label' => __('Licence approved by MedFind'), 'url' => $routes['documents']];
        $item = fn (bool $done, string $label, string $url) => compact('done', 'label', 'url');
        return match ($type) {
            'doctor' => [
                $item(filled($client->image), __('Profile photo'), $routes['profile']),
                $item($text($client->translation?->about) >= 80, __('About you, at least two sentences'), $routes['profile']),
                $item($text($client->translation?->educations) > 0 && $text($client->translation?->qualifications) > 0, __('Education and qualifications'), $routes['profile']),
                $has('consultation_fee') ? $item((float) $client->fee > 0, __('Consultation fee'), $routes['profile']) : null,
                $has('insurance') ? $item($text($client->translation?->accepted_insurance) > 0, __('Insurance accepted'), $routes['profile']) : null,
                $has('services') ? $item(method_exists($client, 'services') && $client->services()->exists(), __('Services offered'), $routes['profile']) : null,
                $has('whatsapp') ? $item(filled($client->whatsapp), __('WhatsApp number'), $routes['profile']) : null,
                $licence,
            ],
            'clinic' => [
                $item(filled($client->logo), __('Logo or photo'), $routes['profile']),
                $item($text($client->description) >= 80, __('About text, at least two sentences'), $routes['profile']),
                $has('operating_hours') ? $item(filled($client->operating_hours), __('Opening hours'), $routes['profile']) : null,
                $has('services_limit') ? $item((Schema::hasTable('clinic_service') && $client->services()->exists()) || filled($client->featured_services), __('Services offered'), $routes['profile']) : null,
                $has('insurance') ? $item(filled($client->insurance_accepted), __('Insurance accepted'), $routes['profile']) : null,
                $has('maps') ? $item(filled($client->google_maps_link), __('Google Maps link'), $routes['profile']) : null,
                $has('gallery_limit') ? $item($client->galleryImages()->exists(), __('Gallery photos'), $routes['gallery']) : null,
                $licence,
            ],
            'school' => [
                $item(filled($client->logo), __('Logo'), $routes['profile']),
                $item($text($client->about) >= 80, __('About the institution, at least two sentences'), $routes['profile']),
                $item(filled($client->location), __('Location'), $routes['profile']),
                $has('courses_limit') ? $item($client->courses()->where('approval_status', 'approved')->exists(), __('At least one approved course'), $routes['items']) : null,
                $has('website') ? $item(filled($client->website), __('Website link'), $routes['profile']) : null,
                $has('whatsapp') ? $item(filled($client->whatsapp), __('WhatsApp number'), $routes['profile']) : null,
                $licence,
            ],
            'supplier' => [
                $item(filled($client->logo), __('Logo'), $routes['profile']),
                $item($text($client->description) >= 80, __('About the company, at least two sentences'), $routes['profile']),
                $has('areas_served') ? $item(!empty($client->counties_served), __('Counties served'), $routes['profile']) : $item(filled($client->address), __('Address'), $routes['profile']),
                $has('categories') ? $item($client->categories()->exists(), __('Product categories'), $routes['profile']) : null,
                $has('products_limit') ? $item($client->products()->where('approval_status', 'approved')->exists(), __('At least one approved product'), $routes['items']) : null,
                $has('website') ? $item(filled($client->website), __('Website link'), $routes['profile']) : null,
                $licence,
            ],
        };
    }

    private function actions(string $type, \Closure $has, array $routes): array
    {
        $a = fn ($label, $hint, $icon, $tone, $url) => compact('label', 'hint', 'icon', 'tone', 'url');
        return [
            $a(__('Edit profile'), __('What patients see'), 'fa-pen', 'blue', $routes['profile']),
            $type === 'school' && $has('courses_limit') ? $a(__('Courses'), __('Add and edit courses'), 'fa-book-open', 'violet', $routes['items']) : null,
            $type === 'supplier' && $has('products_limit') ? $a(__('Products'), __('Add, edit or import'), 'fa-boxes', 'violet', $routes['items']) : null,
            $type === 'clinic' && $has('gallery_limit') ? $a(__('Gallery'), __('Photos & branches'), 'fa-images', 'violet', $routes['gallery']) : null,
            $type === 'doctor' ? $a(__('Working hours'), __('When you see patients'), 'fa-clock', 'violet', $routes['schedule']) : null,
            $a(__('Licence'), __('Verification documents'), 'fa-id-card', 'green', $routes['documents']),
            $has('inquiries') ? $a($type === 'supplier' ? __('Quotations') : __('Enquiries'), __('Reply to people'), 'fa-inbox', 'amber', $routes['inquiries']) : null,
            $a(__('Receipts'), __('Download PDFs'), 'fa-file-invoice', 'grey', $routes['receipts']),
            $a(__('Messages'), __('From MedFind'), 'fa-bell', 'blue', $routes['messages']),
        ];
    }

    /** Plan features worth naming, by type: key => label. */
    public static function labels(string $type): array
    {
        $common = ['search_visibility' => __('Listed in search'), 'verified_badge' => __('Verified badge'), 'homepage_rotation' => __('Home page spot'), 'analytics' => __('Analytics'),
            'inquiries' => __('Enquiries inbox'), 'whatsapp' => __('WhatsApp button'), 'website' => __('Website link'), 'promotional_banner' => __('Promotional banner')];
        return $common + match ($type) {
            'doctor' => ['contact' => __('Contact details'), 'consultation_fee' => __('Consultation fee'), 'services' => __('Services'), 'insurance' => __('Insurance list'), 'telemedicine' => __('Telemedicine tag'),
                'emergency' => __('Emergency tag'), 'reviews' => __('Patient reviews'), 'top_specialist_badge' => __('Top specialist badge')],
            'clinic' => ['contact' => __('Contact details'), 'description' => __('Full description'), 'insurance' => __('Insurance list'), 'maps' => __('Google Maps'), 'top_clinic_badge' => __('Top facility badge'),
                'services_limit' => __('Services'), 'highlights_limit' => __('Highlights'), 'cover_photo' => __('Cover photo'), 'gallery_limit' => __('Gallery'), 'equipment' => __('Equipment'), 'reviews' => __('Patient reviews'),
                'emergency' => __('Emergency tag'), 'operating_hours' => __('Opening hours'), 'branches' => __('Branches'), 'featured_services' => __('Featured services')],
            'school' => ['courses_limit' => __('Courses'), 'featured_courses' => __('Featured courses'), 'intake_promotion' => __('Intake promotion'), 'institution_profile' => __('Institution profile'), 'photos' => __('Photos'),
                'location_map' => __('Location map'), 'application_information' => __('Application info'), 'campus_facilities' => __('Campus facilities'), 'practical_training' => __('Practical training'),
                'accommodation' => __('Accommodation'), 'international_pathways' => __('International pathways'), 'featured_intake_campaign' => __('Featured intake campaign')],
            'supplier' => ['products_limit' => __('Products'), 'catalogue' => __('Catalogues'), 'gallery' => __('Gallery'), 'gallery_limit' => __('Gallery photos'), 'services' => __('Services'), 'supplier_profile' => __('Supplier profile'),
                'categories' => __('Categories'), 'supplier_types' => __('Supplier types'), 'product_images' => __('Product images'), 'areas_served' => __('Areas served'), 'brands' => __('Brands'),
                'social_links' => __('Social links'), 'featured_products' => __('Featured products'), 'featured_supplier' => __('Featured supplier')],
        };
    }

    private function included(string $type, array $benefits): array
    {
        $labels = self::labels($type);
        return collect($benefits)->filter(fn ($v, $k) => isset($labels[$k]) && ($v || (str_ends_with($k, '_limit') && is_numeric($v))))->keys()->map(fn ($k) => $labels[$k])->unique()->values()->all();
    }

    /** Features of this type's top package that the current one lacks (for "upgrade to unlock"). */
    private function locked(string $type, array $benefits): array
    {
        $labels = self::labels($type);
        $top = collect(app(SubscriptionPlanService::class)->for($type))->sortByDesc('price')->first();
        return collect($top['entitlements'] ?? [])->filter(fn ($v, $k) => $v && isset($labels[$k]) && empty($benefits[$k]) && !(str_ends_with($k, '_limit') && array_key_exists($k, $benefits)))
            ->keys()->map(fn ($k) => $labels[$k])->unique()->take(6)->values()->all();
    }

    private function meters(string $type, Model $client, array $benefits, array $usage): array
    {
        $out = [];
        $add = function (string $key, string $label, int $used) use (&$out, $benefits) {
            if (!array_key_exists($key, $benefits) || !is_numeric($benefits[$key])) return;
            $out[] = ['label' => $label, 'used' => $used, 'cap' => (int) $benefits[$key]];
        };
        if ($type === 'clinic') { $add('services_limit', __('Services listed'), (int) ($usage['services_limit'] ?? 0)); $add('gallery_limit', __('Gallery photos'), (int) ($usage['gallery_limit'] ?? 0)); }
        if ($type === 'school') $add('courses_limit', __('Courses'), (int) ($usage['courses_limit'] ?? $client->courses()->count()));
        if ($type === 'supplier') { $add('products_limit', __('Products'), (int) ($usage['products_limit'] ?? $client->products()->count())); $add('gallery_limit', __('Gallery photos'), is_array($client->gallery_images) ? count($client->gallery_images) : 0); }
        return $out;
    }

    private function subtitle(string $type, Model $client): ?string
    {
        return match ($type) {
            'doctor' => trim(($client->department?->name ?? '').($client->translation?->designations ? ' · '.Str::limit(strip_tags($client->translation->designations), 40) : ''), ' ·'),
            'clinic' => $client->specialty,
            'school' => collect([$client->institution_type, $client->location])->filter()->implode(' · '),
            'supplier' => collect((array) ($client->supplier_types ?? []))->take(2)->implode(' · ') ?: __('Medical supplier'),
        } ?: null;
    }

    private function logo(string $type, Model $client): ?string
    {
        $path = $type === 'doctor' ? $client->image : $client->logo;
        if (!$path) return null;
        return asset(in_array($type, ['school', 'supplier'], true) && str_starts_with($path, 'partner-') ? 'uploads/store/'.$path : $path);
    }

    /** A paybill payment waiting for MedFind, so the provider knows it was received. */
    private function pendingPayment(string $type, Model $client): ?array
    {
        $payment = app(ManualPaymentService::class)->openAttempts($client, $type)->first();
        if (!$payment) return null;
        return ['status' => $payment->status, 'plan' => $payment->subscription?->package_name, 'url' => ManualPaymentService::paymentUrl($payment)];
    }
}
