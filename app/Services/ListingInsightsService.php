<?php
namespace App\Services;

use App\Models\ListingEvent;
use App\Models\ProviderProfile;
use Illuminate\Support\Facades\Auth;
use Modules\Doctor\app\Models\ProviderDocument;
use Modules\Doctor\app\Models\ProviderInquiry;

class ListingInsightsService {
    public const EVENTS = ['profile_view', 'search_appearance', 'phone_click', 'whatsapp_click', 'website_click'];

    public function owner(string $type) {
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier'], true), 404);
        return Auth::guard($type)->user() ?? abort(401);
    }

    public function record(string $type, int $id, string $event): void {
        if (!in_array($event, self::EVENTS, true) || Auth::guard('admin')->check() || Auth::guard($type)->id() === $id) return;
        ListingEvent::firstOrCreate(['provider_type' => $type, 'provider_id' => $id, 'event' => $event,
            'day' => now()->toDateString(), 'visitor' => hash_hmac('sha256', request()->session()->getId(), config('app.key'))]);
    }

    public function inquiries(string $type, $client) {
        if (in_array($type, ['doctor', 'clinic'])) {
            abort_unless(app(\Modules\Doctor\app\Services\SubscriptionAccessService::class)->{$type.'Can'}($client, 'inquiries'), 403);
            return ProviderInquiry::where('provider_type', $type)->where('provider_id', $client->id);
        }
        $profile = ProviderProfile::where('provider_type', $type)->where('provider_id', $client->id)->first();
        abort_unless($profile && app(PartnerPortalService::class)->allows($profile, 'inquiries'), 403);
        return $type === 'school' ? $client->enquiries() : $client->quotationRequests();
    }

    public function summary(string $type, $client, int $days): array {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = ListingEvent::where('provider_type', $type)->where('provider_id', $client->id)->whereBetween('day', [$start->toDateString(), now()->toDateString()])
            ->selectRaw('event, day, COUNT(*) as total')->groupBy('event', 'day')->get();
        $totals = collect(self::EVENTS)->mapWithKeys(fn ($event) => [$event => (int) $rows->where('event', $event)->sum('total')])->all();
        $labels = collect(range($days - 1, 0))->map(fn ($offset) => now()->subDays($offset)->toDateString());
        $series = collect(self::EVENTS)->mapWithKeys(fn ($event) => [$event => $labels->map(fn ($day) => (int) $rows->where('event', $event)->where('day', $day)->sum('total'))->all()])->all();
        $setup = app(ProviderOnboardingService::class)->status($client, $type);
        $reminders = [];
        if (!$setup['complete']) $reminders[] = ['text' => __('Complete your listing details, image and verification uploads.'), 'url' => $setup['next_url']];
        if (!$client->updated_at || $client->updated_at->lt(now()->subDays(90))) $reminders[] = ['text' => __('Your listing has not been updated for 90 days. Review your details and images.'), 'url' => $setup['profile_url']];
        $docs = ProviderDocument::where('provider_type', $type)->where('provider_id', $client->id)->get();
        if ($docs->contains(fn ($doc) => $doc->status === 'rejected' || ($doc->accreditation_expires_at && $doc->accreditation_expires_at->lte(now()->addDays(30))))) $reminders[] = ['text' => __('A document is rejected, expired or expires within 30 days. Upload an updated copy.'), 'url' => $setup['documents_url']];
        $profile = ProviderProfile::where('provider_type', $type)->where('provider_id', $client->id)->first();
        if (in_array($type, ['doctor', 'clinic'])) {
            $access = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class);
            $subscription = $type === 'doctor' ? $access->activeDoctorSubscription($client) : $access->activeClinicSubscription($client);
            $benefits = $subscription?->entitlements ?? [];
            if ($subscription && !$benefits) $benefits = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->find($type, $subscription->plan_code)['entitlements'] ?? [];
            $inquiries = ProviderInquiry::where('provider_type', $type)->where('provider_id', $client->id);
        } else {
            $subscription = $profile ? app(PartnerPortalService::class)->subscription($profile) : null;
            $benefits = $profile ? app(PartnerPortalService::class)->entitlements($profile) : [];
            $inquiries = $type === 'school' ? $client->enquiries() : $client->quotationRequests();
        }
        $renewUrl = match ($type) {
            'doctor' => route('listing.doctor-plans'),
            'clinic' => route('clinic.subscription.status'), default => route('website.partner.subscription.renew', ['type' => $type]),
        };
        if (!$subscription || ($subscription->expires_at && $subscription->expires_at->lte(now()->addDays(30)))) $reminders[] = ['text' => __('Your subscription needs renewal or expires within 30 days.'), 'url' => $renewUrl];
        $usage = [];
        if ($type === 'school') $usage['courses_limit'] = $client->courses()->count();
        if ($type === 'supplier') $usage['products_limit'] = $client->products()->count();
        if ($type === 'clinic') {
            if (\Illuminate\Support\Facades\Schema::hasTable('clinic_service')) $usage['services_limit'] = $client->services()->count();
            $usage['gallery_limit'] = $client->galleryImages()->count();
        }
        return compact('type', 'days', 'totals', 'labels', 'series', 'reminders', 'subscription', 'benefits', 'usage', 'renewUrl') + ['inquiry_counts' => (clone $inquiries)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')];
    }
}
