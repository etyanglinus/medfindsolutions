<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * What a provider must fill in before their listing can go live, beyond the basics every profile
 * needs: a real description (not a word or two) and every public section their package shows
 * (WhatsApp button, opening hours, insurance list, services, website, counties served...). A
 * section the package does not include is never demanded. Used by the profile forms (save checks),
 * ProviderOnboardingService (sends incomplete accounts back to their profile) and the reminders.
 */
class ProfileRequirements
{
    /** Shortest description that still tells patients something (visible characters). */
    public const MIN_ABOUT = 80;

    /** Entitlements of the provider's current plan, for any type. */
    public function entitlements(string $type, Model $client): array
    {
        if (in_array($type, ['doctor', 'clinic'], true)) {
            $access = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class);
            $subscription = $type === 'doctor' ? $access->activeDoctorSubscription($client) : $access->activeClinicSubscription($client);
            $benefits = $subscription?->entitlements ?? [];
            if ($subscription && !$benefits) $benefits = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->find($type, $subscription->plan_code)['entitlements'] ?? [];
            return (array) $benefits;
        }
        $profile = $client->providerProfile ?? \App\Models\ProviderProfile::where('provider_type', $type)->where('provider_id', $client->getKey())->first();
        return $profile ? app(PartnerPortalService::class)->entitlements($profile) : [];
    }

    /**
     * Requirements beyond the basics: [key => [label, check(Model): bool]] for the current plan.
     *
     * @return array<string, array{0: string, 1: \Closure}>
     */
    public function extras(string $type, Model $client, ?array $entitlements = null): array
    {
        $e = $entitlements ?? $this->entitlements($type, $client);
        $on = fn (string $key) => !empty($e[$key]) || (str_ends_with($key, '_limit') && array_key_exists($key, $e) && is_numeric($e[$key]));
        $text = fn ($v) => self::visible($v) >= self::MIN_ABOUT;
        $about = __('A description of at least :count characters', ['count' => self::MIN_ABOUT]);
        $out = [];
        if ($type === 'doctor') {
            $out['about'] = [$about, fn ($c) => $c->translations()->get()->contains(fn ($t) => $text($t->about))];
            if ($on('whatsapp')) $out['whatsapp'] = [__('WhatsApp number'), fn ($c) => filled($c->whatsapp)];
            if ($on('consultation_fee')) $out['fee'] = [__('Consultation fee'), fn ($c) => (float) $c->fee > 0];
            if ($on('insurance')) $out['accepted_insurance'] = [__('Insurance accepted'), fn ($c) => $c->translations()->get()->contains(fn ($t) => self::visible($t->accepted_insurance) > 0)];
            if ($on('services') && \Modules\Doctor\app\Models\Service::query()->whereNotNull('name')->where('status', true)->exists()) $out['service_ids'] = [__('At least one service'), fn ($c) => $c->services()->exists()];
        } elseif ($type === 'clinic') {
            $out['description'] = [$about, fn ($c) => $text($c->description)];
            if ($on('whatsapp')) $out['whatsapp'] = [__('WhatsApp number'), fn ($c) => filled($c->whatsapp)];
            if ($on('operating_hours')) $out['operating_hours'] = [__('Opening hours'), fn ($c) => filled($c->operating_hours)];
            if ($on('insurance')) $out['insurance_accepted'] = [__('Insurance accepted'), fn ($c) => filled($c->insurance_accepted)];
            if ($on('services_limit') && Schema::hasTable('clinic_service') && \Illuminate\Support\Facades\DB::table('services')->exists()) {
                $out['services'] = [__('At least one service'), fn ($c) => $c->services()->exists()];
            }
        } elseif ($type === 'school') {
            $out['about'] = [$about, fn ($c) => $text($c->about)];
            if ($on('whatsapp')) $out['whatsapp'] = [__('WhatsApp number'), fn ($c) => filled($c->whatsapp)];
            if ($on('website')) $out['website'] = [__('Website link'), fn ($c) => filled($c->website)];
        } else {
            $out['description'] = [$about, fn ($c) => $text($c->description)];
            if ($on('whatsapp')) $out['whatsapp'] = [__('WhatsApp number'), fn ($c) => filled($c->whatsapp)];
            if ($on('website')) $out['website'] = [__('Website link'), fn ($c) => filled($c->website)];
            if ($on('areas_served')) $out['counties_served'] = [__('Counties served'), fn ($c) => !empty($c->counties_served)];
            if ($on('categories')) $out['category_ids'] = [__('At least one product category'), fn ($c) => $c->categories()->exists()];
        }
        return $out;
    }

    /** Labels of the extras still missing for this provider. */
    public function missing(string $type, Model $client, ?array $entitlements = null): array
    {
        return collect($this->extras($type, $client, $entitlements))->reject(fn ($req) => ($req[1])($client))->map(fn ($req) => $req[0])->values()->all();
    }

    /**
     * Validation rules for the profile form, so a save cannot leave a required section empty.
     * Field names are the form's own (e.g. service_ids for specialists, services for facilities).
     */
    public function rules(string $type, Model $client, ?array $entitlements = null): array
    {
        $extras = $this->extras($type, $client, $entitlements);
        $minText = function (string $attribute, $value, \Closure $fail) {
            if (self::visible($value) < self::MIN_ABOUT) $fail(__('Please write at least :count characters so patients understand what you offer.', ['count' => self::MIN_ABOUT]));
        };
        $rules = [];
        foreach (array_keys($extras) as $key) {
            $rules[$key] = match ($key) {
                'about', 'description' => ['required', 'string', $minText],
                'fee' => ['required', 'numeric', 'gt:0'],
                'service_ids', 'services', 'counties_served', 'category_ids' => ['required', 'array', 'min:1'],
                'website' => ['required', 'url', 'max:255'],
                default => ['required', 'string', 'max:2000'],
            };
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'whatsapp.required' => __('Your plan shows a WhatsApp button, so add a WhatsApp number.'),
            'operating_hours.required' => __('Your plan shows opening hours, so add them (e.g. Mon–Fri 8am–6pm).'),
            'insurance_accepted.required' => __('Your plan shows the insurance you accept, so list it (e.g. SHA, AAR, Jubilee).'),
            'accepted_insurance.required' => __('Your plan shows the insurance you accept, so list it (e.g. SHA, AAR, Jubilee).'),
            'services.required' => __('Choose at least one service you offer.'), 'service_ids.required' => __('Choose at least one service you offer.'),
            'fee.gt' => __('Enter your consultation fee in KES.'),
            'website.required' => __('Your plan shows a website link, so add your website.'),
            'counties_served.required' => __('Choose at least one county you serve.'),
            'category_ids.required' => __('Choose at least one product category.'),
        ];
    }

    public static function visible($value): int
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_strlen(preg_replace('/[\s\x{00A0}\x{200B}]+/u', ' ', trim($text)));
    }
}
