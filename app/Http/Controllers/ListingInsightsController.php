<?php
namespace App\Http\Controllers;
use App\Services\ListingInsightsService;
use Illuminate\Http\Request;
class ListingInsightsController extends Controller {
    public function doctorPlans(ListingInsightsService $service) {
        $doctor = $service->owner('doctor');
        if (!$doctor->registration_token) $doctor->forceFill(['registration_token' => \Illuminate\Support\Str::random(80)])->save();
        return redirect()->route('doctor.subscription.complete', ['token' => $doctor->registration_token]);
    }
    public function inquiries(Request $request, string $type, ListingInsightsService $service) {
        $data = $request->validate(['status' => ['nullable', 'in:new,contacted,closed,quoted,reviewed,replied']]);
        $query = $service->inquiries($type, $service->owner($type));
        if (!empty($data['status'])) $query->where('status', $data['status']);
        $inquiries = $query->latest()->paginate(20)->withQueryString();
        $layout = match ($type) {'doctor' => 'doctor.master_layout', 'clinic' => 'clinic.master_layout', default => 'provider.partner-layout'};
        $contentSection = match ($type) {'doctor' => 'admin-content', 'clinic' => 'clinic-content', default => 'partner-content'};
        // The partner layout requires the provider context used throughout its navigation.
        $record = $service->owner($type);
        $profile = $record->providerProfile;
        $subscription = $profile ? app(\App\Services\PartnerPortalService::class)->subscription($profile) : null;
        $entitlements = $profile ? app(\App\Services\PartnerPortalService::class)->entitlements($profile) : [];
        return view('provider.listing-inquiries', compact('type', 'inquiries', 'layout', 'contentSection', 'record', 'profile', 'subscription', 'entitlements'));
    }

    public function updateInquiry(Request $request, string $type, int $inquiry, ListingInsightsService $service) {
        $owner = $service->owner($type);
        $query = $service->inquiries($type, $owner);
        $data = $request->validate(['status' => ['required', 'in:new,contacted,closed']]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($query, $inquiry, $data, $type, $owner) {
            $item = $query->whereKey($inquiry)->lockForUpdate()->firstOrFail();
            if ($item->status !== $data['status']) {
                $item->update($data);
                if ($type === 'supplier') $item->statusHistory()->create(['status' => $data['status'], 'actor_type' => 'supplier', 'actor_id' => $owner->id]);
            }
        });
        return back()->with('message', __('Inquiry status updated.'));
    }
    public function track(Request $request, string $type, int $provider, string $event, ListingInsightsService $service) {
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier'], true) && in_array($event, ['phone_click', 'whatsapp_click', 'website_click'], true), 404);
        $model = \App\Services\ProviderPaystackService::model($type);
        $model::findOrFail($provider);
        $service->record($type, $provider, $event);
        return response()->noContent();
    }
    public function report(Request $request, string $type, ListingInsightsService $service) {
        $data = $request->validate(['days' => ['nullable', 'integer', 'in:7,30,90']]);
        $summary = $service->summary($type, $service->owner($type), (int) ($data['days'] ?? 30));
        return response()->streamDownload(function () use ($summary) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', ...ListingInsightsService::EVENTS]);
            foreach ($summary['labels'] as $index => $day) fputcsv($file, [$day, ...array_map(fn ($event) => $summary['series'][$event][$index], ListingInsightsService::EVENTS)]);
            fclose($file);
        }, $type.'-listing-engagement.csv', ['Content-Type' => 'text/csv']);
    }
}
