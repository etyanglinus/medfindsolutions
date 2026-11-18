<?php

namespace App\Services\Tafuta;

use App\Services\LicenceVerification;
use App\Services\PartnerPortalService;
use App\Services\PublicDirectory;
use App\Support\KenyaPlaces;
use App\Support\PlanRanking;
use Illuminate\Support\Str;
use Modules\Doctor\app\Services\SubscriptionPlanService;

/**
 * The tools Tafuta can call. They are read-only and return only what the public site already shows,
 * using the same "who is listed" rule as the directory (PublicDirectory), so Tafuta can never
 * recommend a provider that is hidden, unpaid or expired. Fees and insurance are included only
 * when the provider's plan shows them on their profile.
 */
class TafutaTools
{
    public const TYPES = ['doctor', 'clinic', 'school', 'supplier'];
    private const LIMIT = 5;

    /** Providers returned during the current reply, for the cards under Tafuta's answer. */
    public array $found = [];

    /** @return list<array<string, mixed>> tool definitions in API wire format */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'search_providers',
                'description' => 'Search MedFind\'s public directory of healthcare providers in Kenya. Returns up to 5 listed providers, best plan first, each with its public profile link. '
                    .'Use type "doctor" for specialists, "clinic" for facilities (clinics, hospitals, labs, pharmacies), "school" for healthcare training institutions and their courses, "supplier" for medical equipment and supply companies. '
                    .'Call it before naming any provider; never invent providers. If nothing matches, try again with fewer filters (drop the place, then the keywords).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => self::TYPES, 'description' => 'Kind of provider.'],
                        'keywords' => ['type' => 'string', 'description' => 'Words to match in the name, specialty, services, courses or products, e.g. "dental", "x-ray", "nursing diploma", "wheelchair".'],
                        'specialty' => ['type' => 'string', 'description' => 'For doctors only: specialty name, e.g. "Cardiology", "Paediatrics", "ENT".'],
                        'place' => ['type' => 'string', 'description' => 'County, town or Nairobi area, e.g. "Kisumu", "Westlands".'],
                        'insurance' => ['type' => 'string', 'description' => 'Doctors and clinics: insurer the provider must accept, e.g. "SHA", "AAR", "Jubilee".'],
                        'max_fee' => ['type' => 'integer', 'description' => 'Doctors and clinics: highest consultation fee in KES.'],
                    ],
                    'required' => ['type'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'list_specialties',
                'description' => 'List the medical specialties that currently have listed specialists on MedFind, with how many each and a link. Use it when the visitor is unsure which specialist to see or asks what is available.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass, 'additionalProperties' => false],
            ],
            [
                'name' => 'platform_help',
                'description' => 'Facts and links about using MedFind: joining as a provider, plan prices, how to pay (Paystack card/M-Pesa or M-Pesa paybill), receipts, why a listing is not showing, and how to contact the MedFind team. Use it for any question about MedFind itself instead of guessing.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => ['type' => 'string', 'enum' => ['join', 'plans', 'payment', 'receipts', 'listing_not_showing', 'contact', 'privacy']],
                        'provider_type' => ['type' => 'string', 'enum' => self::TYPES, 'description' => 'For "plans": which kind of provider.'],
                    ],
                    'required' => ['topic'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function run(string $name, array $input): array
    {
        return match ($name) {
            'search_providers' => $this->search($input),
            'list_specialties' => $this->specialties(),
            'platform_help' => $this->help((string) ($input['topic'] ?? ''), $input['provider_type'] ?? null),
            default => ['error' => 'Unknown tool.'],
        };
    }

    private function search(array $in): array
    {
        $type = in_array($in['type'] ?? null, self::TYPES, true) ? $in['type'] : 'doctor';
        $keywords = trim(Str::limit((string) ($in['keywords'] ?? ''), 80, ''));
        $specialty = trim(Str::limit((string) ($in['specialty'] ?? ''), 60, ''));
        $insurance = trim(Str::limit((string) ($in['insurance'] ?? ''), 40, ''));
        $maxFee = isset($in['max_fee']) && is_numeric($in['max_fee']) ? (int) $in['max_fee'] : null;
        $place = $this->place((string) ($in['place'] ?? ''));
        $directory = app(PublicDirectory::class);
        $query = $directory->query($type);
        $table = $query->getModel()->getTable();

        if ($place) $query->whereIn("$table.id", $directory->ids($type, $place));

        if ($type === 'doctor') {
            $query->with(['translation', 'department.translation', 'location.translation', 'subscriptions'])
                ->when($specialty !== '', fn ($q) => $q->whereHas('department.translations', fn ($d) => $d->where('name', 'like', "%{$specialty}%")))
                ->when($keywords !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$keywords}%")
                    ->orWhereHas('department.translations', fn ($d) => $d->where('name', 'like', "%{$keywords}%"))
                    ->orWhereHas('translations', fn ($t) => $t->where('designations', 'like', "%{$keywords}%")->orWhere('about', 'like', "%{$keywords}%"))))
                ->when($insurance !== '', fn ($q) => $q->whereHas('translations', fn ($t) => $t->where('accepted_insurance', 'like', "%{$insurance}%")))
                ->when($maxFee !== null, fn ($q) => $q->where('fee', '<=', $maxFee));
        } elseif ($type === 'clinic') {
            $query->with('subscriptions')
                ->when($keywords !== '' || $specialty !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.($keywords ?: $specialty).'%')
                    ->orWhere('specialty', 'like', '%'.($keywords ?: $specialty).'%')->orWhere('description', 'like', '%'.($keywords ?: $specialty).'%')->orWhere('featured_services', 'like', '%'.($keywords ?: $specialty).'%')))
                ->when($insurance !== '', fn ($q) => $q->where('insurance_accepted', 'like', "%{$insurance}%"))
                ->when($maxFee !== null, fn ($q) => $q->where('consultation_fee', '<=', $maxFee));
        } elseif ($type === 'school') {
            $query->with(['courses' => fn ($c) => $c->where('approval_status', 'approved')->orderBy('name')])
                ->when($keywords !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$keywords}%")->orWhere('institution_type', 'like', "%{$keywords}%")
                    ->orWhereHas('courses', fn ($c) => $c->where('approval_status', 'approved')->where('name', 'like', "%{$keywords}%"))));
        } else {
            $query->with(['categories', 'products' => fn ($p) => $p->where('approval_status', 'approved')->where('is_published', true)->latest()->take(5)])
                ->when($keywords !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$keywords}%")->orWhere('description', 'like', "%{$keywords}%")
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', "%{$keywords}%"))
                    ->orWhereHas('products', fn ($p) => $p->where('approval_status', 'approved')->where('is_published', true)->where(fn ($n) => $n->where('name', 'like', "%{$keywords}%")->orWhere('brand', 'like', "%{$keywords}%")))));
        }

        $total = (clone $query)->count();
        $rows = PlanRanking::apply($query, $type)->take(self::LIMIT)->get();
        $results = $rows->map(fn ($row) => $this->card($type, $row))->values()->all();
        foreach ($results as $card) $this->found[$card['type'].':'.$card['url']] = $card;

        return [
            'total_matches' => $total,
            'results' => array_map(fn ($c) => array_diff_key($c, ['image' => 1]), $results),
            'see_all_url' => $this->seeAllUrl($type, $place, $specialty, $keywords),
            'note' => $total === 0 ? 'No listed providers match. Try fewer filters, or point the visitor to see_all_url.' : null,
        ];
    }

    private function card(string $type, $row): array
    {
        $verified = app(LicenceVerification::class)->isVerified($type, $row->id);
        $card = ['type' => $type, 'name' => $row->name, 'verified_licence' => $verified, 'featured' => (int) ($row->plan_tier ?? 0) >= PlanRanking::FEATURED_TIER];
        if ($type === 'doctor') {
            $plan = $row->subscriptions->where('status', 'approved')->first(fn ($s) => !$s->expires_at || $s->expires_at->isFuture());
            $card += [
                'specialty' => $row->department?->name,
                'title' => $row->translation?->designations,
                'location' => trim(collect([$row->location?->name, Str::limit(strip_tags((string) $row->translation?->address), 60, '')])->filter()->implode(', ')),
                'url' => route('website.doctor.details', $row->slug),
                'image' => $row->image ? url($row->image) : null,
            ];
            if ($row->fee > 0 && $plan?->hasEntitlement('consultation_fee')) $card['consultation_fee_kes'] = (int) $row->fee;
            if ($plan?->hasEntitlement('insurance') && $row->translation?->accepted_insurance) $card['insurance'] = Str::limit(strip_tags($row->translation->accepted_insurance), 120);
        } elseif ($type === 'clinic') {
            $plan = $row->subscriptions->where('status', 'approved')->first(fn ($s) => !$s->expires_at || $s->expires_at->isFuture());
            $card += ['specialty' => $row->specialty, 'location' => $row->location ?: Str::limit((string) $row->address, 60, ''), 'url' => route('website.facility.details', $row->slug),
                'image' => $row->logo ? url($row->logo) : null, 'emergency' => (bool) $row->emergency_available];
            if ($row->consultation_fee > 0) $card['consultation_fee_kes'] = (int) $row->consultation_fee;
            if ($plan?->hasEntitlement('insurance') && $row->insurance_accepted) $card['insurance'] = Str::limit(strip_tags((string) $row->insurance_accepted), 120);
        } elseif ($type === 'school') {
            $card += ['specialty' => $row->institution_type, 'location' => $row->location, 'url' => route('website.healthcare-institution', $row->slug),
                'image' => $row->logo ? asset(str_starts_with($row->logo, 'partner-') ? 'uploads/store/'.$row->logo : $row->logo) : null,
                'courses' => $row->courses->take(6)->pluck('name')->all()];
        } else {
            $card += ['specialty' => $row->categories->pluck('name')->take(3)->implode(', '), 'location' => implode(', ', array_slice((array) $row->counties_served, 0, 4)) ?: Str::limit((string) $row->address, 60, ''),
                'url' => route('website.medical-supplier', $row->slug), 'image' => $row->logo ? asset(str_starts_with($row->logo, 'partner-') ? 'uploads/store/'.$row->logo : $row->logo) : null,
                'products' => $row->products->pluck('name')->all()];
        }
        return array_filter($card, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    private function specialties(): array
    {
        return ['specialties' => app(PublicDirectory::class)->specialties()->map(fn ($row) => [
            'name' => $row['department']->name, 'listed_specialists' => $row['count'], 'url' => route('website.seo.specialty', $row['slug']),
        ])->values()->all(), 'all_specialists_url' => route('website.doctors')];
    }

    private function help(string $topic, ?string $type): array
    {
        $paybill = app(\App\Services\ManualPaymentService::class);
        $settings = $paybill->paybillEnabled() ? $paybill->settings() : null;
        $joinLinks = ['specialist_or_facility' => route('provider.register'), 'school' => route('website.partner.register', 'school'), 'supplier' => route('website.partner.register', 'supplier')];
        return match ($topic) {
            'join' => ['how' => 'Choose your provider type, create an account, confirm your email, pick a plan and pay. Then complete the profile and upload your licence; the MedFind team reviews it and the listing goes live.', 'links' => $joinLinks],
            'plans' => ['plans' => collect($type ? [$type] : self::TYPES)->mapWithKeys(fn ($t) => [$t => collect(app(SubscriptionPlanService::class)->for($t))->map(fn ($p) => ['name' => $p['name'], 'price_kes' => $p['price'], 'months' => $p['duration_months'], 'features' => array_slice((array) $p['features'], 0, 6)])->values()->all()])->all(),
                'note' => 'Higher plans appear first in search.', 'links' => $joinLinks],
            'payment' => ['options' => array_values(array_filter([
                'Paystack: card or M-Pesa, activates automatically once Paystack confirms.',
                $settings ? "M-Pesa Paybill: pay to paybill {$settings->paybill_number}, account {$settings->account_number}, then enter the M-Pesa code on the payment page; the MedFind team confirms it, usually within one working day." : null,
            ])), 'where' => 'Choose "Pay" or "Renew" on your plan page after signing in.', 'sign_in' => route('login')],
            'receipts' => ['how' => 'Every confirmed payment has a PDF receipt: use the link in the "Payment received" email, or open "Payment receipts" in your MedFind portal after signing in.', 'sign_in' => route('login')],
            'listing_not_showing' => ['common_reasons' => ['The plan has expired: renew from your portal.', 'Payment not yet confirmed (M-Pesa paybill payments wait for the team to check the code).', 'Schools and suppliers: the licence document is waiting for review, or the listing is not published yet.', 'The account was switched off by the MedFind team.'],
                'next_step' => 'Sign in to your portal to see your plan status, or contact MedFind.', 'sign_in' => route('login'), 'contact' => url('/contact-us')],
            'contact' => ['contact_page' => url('/contact-us')],
            'privacy' => ['summary' => 'Tafuta keeps this chat for 7 days so you can continue it, then deletes it. Do not share ID numbers or detailed medical records here.', 'privacy_policy' => route('website.privacy-policy')],
            default => ['error' => 'Unknown topic.'],
        };
    }

    /** Free text to a known county/area name (or null). */
    private function place(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') return null;
        foreach (KenyaPlaces::all() as $slug => $name) {
            if (strcasecmp($name, $text) === 0 || strcasecmp($slug, Str::slug($text)) === 0) return $name;
        }
        foreach (KenyaPlaces::all() as $name) if (KenyaPlaces::mentions($text, $name)) return $name;
        return null;
    }

    private function seeAllUrl(string $type, ?string $place, string $specialty, string $keywords): string
    {
        $placeSlug = $place ? Str::slug($place) : null;
        if ($type === 'doctor' && $specialty !== '') {
            $dept = app(PublicDirectory::class)->specialties()->first(fn ($row) => stripos((string) $row['department']->name, $specialty) !== false);
            if ($dept) return $placeSlug ? route('website.seo.specialty-place', [$dept['slug'], $placeSlug]) : route('website.seo.specialty', $dept['slug']);
        }
        return match ($type) {
            'doctor' => route('website.doctors', array_filter(['keyword' => $keywords ?: null])),
            'clinic' => $placeSlug ? route('website.seo.facilities', $placeSlug) : route('website.facilities'),
            'school' => $placeSlug ? route('website.seo.schools', $placeSlug) : route('website.healthcare-courses', array_filter(['q' => $keywords ?: null])),
            'supplier' => $placeSlug ? route('website.seo.suppliers', $placeSlug) : route('website.medical-suppliers', array_filter(['q' => $keywords ?: null])),
        };
    }
}
