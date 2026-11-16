<?php
namespace App\Http\Middleware;
use App\Services\ListingInsightsService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
class TrackListingEngagement {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);
        $view = $response->original ?? null;
        if (!$request->isMethod('GET') || $response->getStatusCode() !== 200 || !$view instanceof View) return $response;
        $name = $view->name(); $data = $view->getData();
        $profiles = ['patient.doctor.show' => ['doctor', 'doctor'], 'patient.facility.show' => ['clinic', 'clinic'], 'patient.healthcare-institution-show' => ['school', 'institution'], 'patient.medical-supplier-show' => ['supplier', 'supplier'], 'patient.medical-supplier-product-show' => ['supplier', 'supplier']];
        $directories = ['patient.doctor.index' => ['doctor', 'doctors'], 'patient.facility.index' => ['clinic', 'clinics'], 'patient.healthcare-courses' => ['school', 'institutions'], 'patient.healthcare-course-show' => ['school', 'institutions'], 'patient.medical-suppliers' => ['supplier', 'suppliers']];
        $service = app(ListingInsightsService::class);
        if (isset($directories[$name])) {
            [$type, $key] = $directories[$name];
            foreach ($data[$key] ?? [] as $client) $service->record($type, $client->id, 'search_appearance');
        }
        if (isset($profiles[$name])) {
            [$type, $key] = $profiles[$name]; $client = $data[$key] ?? null;
            if (!$client) return $response;
            if ($name !== 'patient.medical-supplier-product-show') $service->record($type, $client->id, 'profile_view');
            $urls = [];
            foreach (['phone_click', 'whatsapp_click', 'website_click'] as $event) $urls[$event] = \Illuminate\Support\Facades\URL::temporarySignedRoute('listing.track', now()->addHours(12), ['type' => $type, 'provider' => $client->id, 'event' => $event]);
            $script = view('provider.partials.track-listing', ['trackingUrls' => $urls, 'listingWebsite' => $client->website ?? $client->website_url, 'listingPhones' => [$client->phone, $client->whatsapp]])->render();
            $response->setContent(str_replace('</body>', $script.'</body>', $response->getContent()));
        }
        return $response;
    }
}
