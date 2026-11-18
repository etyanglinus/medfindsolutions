<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireProviderOnboarding;
use App\Services\ProviderOnboardingService;
use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Doctor\app\Models\ProviderDocument;
use Tests\TestCase;

class MandatoryProviderOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $type)
    {
        $model = ProviderPaystackService::model($type);
        $client = new $model;
        $values = ['name' => 'Client', 'slug' => $type.'-client', 'phone' => '0700000000', 'password' => bcrypt('password'), 'email_verified_at' => now(), $type === 'doctor' || $type === 'clinic' ? 'email' : 'official_email' => $type.'@example.test'];
        if ($type === 'doctor') $values += ['department_id' => \Modules\Doctor\app\Models\Department::create(['slug' => 'specialty'])->id, 'location_id' => \Modules\Doctor\app\Models\Location::create(['status' => 1])->id, 'years_of_experience' => '0'];
        if ($type === 'clinic') $values += ['specialty' => 'Facility', 'licensing_body' => 'Body', 'description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi', 'educations' => 'Education', 'experience' => 'Experience', 'qualifications' => 'Qualifications'];
        if ($type === 'school') $values += ['institution_type' => 'College', 'ownership' => 'Private', 'about' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'location' => 'Nairobi'];
        if ($type === 'supplier') $values += ['description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi'];
        $client->forceFill($values)->save();
        if ($type === 'doctor') $client->translations()->create(['lang_code' => 'en', 'licensing_body' => 'Body', 'about' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi', 'educations' => 'Education', 'experience' => 'Experience', 'qualifications' => 'Qualifications']);
        return $client;
    }

    private function request(string $type, string $page, bool $json = false): Request
    {
        $name = in_array($type, ['doctor', 'clinic']) ? $type.'.'.$page : 'website.partner.'.$page;
        $request = Request::create('/'.$type.'/'.$page);
        $route = (new Route('GET', $type.'/'.$page, fn () => null))->name($name)->defaults('type', $type);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        if ($json) $request->headers->set('Accept', 'application/json');
        return $request;
    }

    public function test_all_four_provider_types_must_upload_photo_and_documents_before_dashboard_access(): void
    {
        foreach (['doctor', 'clinic', 'school', 'supplier'] as $type) {
            $client = $this->client($type);
            $this->actingAs($client, $type);
            $service = app(ProviderOnboardingService::class);
            $middleware = app(RequireProviderOnboarding::class);
            $request = $this->request($type, 'dashboard');
            $status = $service->status($client, $type);
            $this->assertFalse($status['complete']);
            $this->assertSame($status['profile_url'], $middleware->handle($request, fn () => response('Dashboard'))->getTargetUrl());
            $client->forceFill([$type === 'doctor' ? 'image' : 'logo' => 'uploaded-photo.jpg'])->save();
            $status = $service->status($client, $type);
            $this->assertSame($status['documents_url'], $middleware->handle($request, fn () => response('Dashboard'))->getTargetUrl());
            $document = ProviderDocument::create(['provider_type' => $type, 'provider_id' => $client->id, 'licensing_body' => 'Body', 'registration_number' => 'REG123', 'file_path' => 'proof.pdf', 'status' => 'pending']);
            $this->assertTrue($service->status($client, $type)['complete']);
            $this->assertSame(200, $middleware->handle($request, fn () => response('Dashboard'))->getStatusCode());
            if ($type === 'doctor') $client->translations()->update(['about' => '<p><br>&nbsp;</p>']);
            else $client->forceFill([$type === 'school' ? 'about' : 'description' => '<p><br>&nbsp;</p>'])->save();
            $this->assertFalse($service->status($client, $type)['complete']);
            if ($type === 'doctor') $client->translations()->update(['about' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.']);
            else $client->forceFill([$type === 'school' ? 'about' : 'description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.'])->save();
            $document->update(['status' => 'rejected']);
            $this->assertFalse($service->status($client, $type)['complete']);
        }
    }

    public function test_incomplete_accounts_can_access_setup_security_and_logout_but_cannot_bypass_with_json(): void
    {
        $client = $this->client('supplier');
        $this->actingAs($client, 'supplier');
        $middleware = app(RequireProviderOnboarding::class);
        foreach (['profile', 'profile.update', 'documents.index', 'documents.store', 'account', 'account.update', 'logout'] as $page) {
            $this->assertSame(200, $middleware->handle($this->request('supplier', $page), fn () => response('Allowed'))->getStatusCode());
        }
        $response = $middleware->handle($this->request('supplier', 'products.store', true), fn () => response('Blocked'));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['onboarding']['complete']);
    }

    public function test_real_login_redirects_each_paid_client_to_mandatory_profile_setup(): void
    {
        \Illuminate\Support\Facades\Cache::put('setting', (object) ['recaptcha_status' => 'inactive']);
        $this->withoutMiddleware([\App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\PreventXSSAttackMiddleware::class, \App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        $this->withoutMiddleware([\Modules\Installer\app\Http\Middleware\SetupMiddleware::class, \Modules\Installer\app\Http\Middleware\PurchaseVerifyMiddleware::class]);
        \Illuminate\Support\Facades\DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        foreach (['doctor', 'clinic', 'school', 'supplier'] as $type) {
            $client = $this->client($type);
            $service = app(ProviderPaystackService::class);
            $plan = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type)[0];
            $payment = $service->createAttempt($client, $type, $plan['id']);
            $service->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => $type.'@example.test']]);
            $login = in_array($type, ['doctor', 'clinic']) ? route($type.'.login') : route('website.partner.login.store', ['type' => $type]);
            $dashboard = in_array($type, ['doctor', 'clinic']) ? route($type.'.dashboard') : route('website.partner.dashboard', ['type' => $type]);
            $this->post($login, ['email' => $type.'@example.test', 'password' => 'password'])->assertRedirect($dashboard);
            $this->get($dashboard)->assertRedirect(app(ProviderOnboardingService::class)->status($client->fresh(), $type)['profile_url']);
        }
    }

    public function test_partner_profile_and_document_uploads_complete_the_real_onboarding_flow(): void
    {
        \Illuminate\Support\Facades\Cache::put('setting', (object) ['app_name' => 'Medfind Test', 'favicon' => '', 'logo' => '', 'default_avatar' => '', 'recaptcha_status' => 'inactive', 'version' => '1.0']);
        $this->withoutMiddleware([\Modules\Installer\app\Http\Middleware\SetupMiddleware::class, \Modules\Installer\app\Http\Middleware\PurchaseVerifyMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\PreventXSSAttackMiddleware::class, \App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        foreach (['school', 'supplier'] as $type) {
            $client = $this->client($type);
            $service = app(ProviderPaystackService::class);
            $plan = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type)[0];
            $payment = $service->createAttempt($client, $type, $plan['id']);
            $service->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => $type.'@example.test']]);
            $this->actingAs($client->fresh(), $type);
            $data = ['name' => 'Client', 'phone' => '0700000000'];
            $about = 'A healthcare organisation serving patients and professionals across Kenya with quality, affordable services.';
            $data += $type === 'school' ? ['institution_type' => 'College', 'ownership' => 'Private', 'about' => $about, 'location' => 'Nairobi'] : ['about' => $about, 'address' => 'Nairobi headquarters'];
            // Everything the package shows publicly is compulsory (WhatsApp, website, counties, categories...).
            $data += ['whatsapp' => '0700000001', 'website' => 'https://example.test'];
            if ($type === 'supplier') {
                $category = \Modules\MedicalSupplier\app\Models\SupplierCategory::create(['name' => 'Diagnostics', 'slug' => 'diagnostics', 'is_active' => true]);
                $data += ['counties_served' => ['Nairobi'], 'category_ids' => [$category->id]];
                $this->putJson(route('website.partner.profile.update', ['type' => $type]), array_diff_key($data, ['counties_served' => 1]))->assertJsonValidationErrors('counties_served');
            }
            $url = route('website.partner.profile.update', ['type' => $type]);
            $this->putJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('logo');
            $data['logo'] = \Illuminate\Http\UploadedFile::fake()->createWithContent('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6GygAAAAASUVORK5CYII='));
            $this->put($url, $data)->assertSessionHasNoErrors()->assertRedirect();
            $client->refresh();
            $this->assertNotEmpty($client->logo);
            $this->assertFalse(app(ProviderOnboardingService::class)->status($client, $type)['complete']);
            $this->post(route('website.partner.documents.store', ['type' => $type]), ['licensing_body' => 'Authority', 'registration_number' => 'REG123', 'license' => \Illuminate\Http\UploadedFile::fake()->createWithContent('license.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF")])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertTrue(app(ProviderOnboardingService::class)->status($client, $type)['complete']);
            $this->get(route('website.partner.dashboard', ['type' => $type]))->assertOk()->assertSee(asset('uploads/store/'.$client->logo));
        }
    }
}
