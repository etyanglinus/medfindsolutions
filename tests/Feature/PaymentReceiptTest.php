<?php

namespace Tests\Feature;

use App\Mail\ProviderPaymentConfirmed;
use App\Models\Admin;
use App\Models\ProviderPaymentTransaction;
use App\Services\PaymentReceipt;
use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        Cache::put('setting', (object) ['app_name' => 'Medfind Test', 'favicon' => '', 'logo' => '', 'default_avatar' => '', 'recaptcha_status' => 'inactive', 'version' => '1.0']);
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class, \App\Http\Middleware\RequireProviderOnboarding::class]);
    }

    protected function paid(string $email = 'sup@example.test'): array
    {
        $supplier = MedicalSupplier::create(['name' => 'Acme Medical', 'slug' => \Illuminate\Support\Str::slug($email), 'official_email' => $email, 'password' => bcrypt('x'), 'registration_token' => \Illuminate\Support\Str::random(20), 'email_verified_at' => now()]);
        $service = app(ProviderPaystackService::class);
        $payment = $service->createAttempt($supplier, 'supplier', 'premium');
        $service->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'channel' => 'mobile_money', 'customer' => ['email' => $email]]);
        return [$supplier, $payment->fresh()];
    }

    public function test_receipt_is_linked_in_email_and_downloadable_by_owner_admin_and_signed_link(): void
    {
        Mail::fake();
        [$supplier, $payment] = $this->paid();
        $this->assertSame('paid', $payment->status);
        Mail::assertSent(ProviderPaymentConfirmed::class, fn ($m) => $m->receiptUrl === PaymentReceipt::signedUrl($payment));

        $pdf = $this->get(PaymentReceipt::signedUrl($payment))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->get(route('provider.receipt.signed', ['transaction' => $payment->id]))->assertForbidden(); // unsigned

        $this->actingAs($supplier, 'supplier');
        $this->get(route('listing.receipts', ['type' => 'supplier']))->assertOk()->assertSee(PaymentReceipt::number($payment));
        $this->get(route('listing.receipts.download', ['type' => 'supplier', 'transaction' => $payment->id]))->assertOk()->assertHeader('content-type', 'application/pdf');

        $admin = tap(new Admin, fn ($a) => $a->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save());
        $this->actingAs($admin, 'admin');
        $this->get(route('admin.provider-payments.receipt', $payment))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_providers_and_unpaid_attempts_have_no_receipt(): void
    {
        Mail::fake();
        [, $payment] = $this->paid();
        [$other] = $this->paid('other@example.test');
        $this->actingAs($other, 'supplier');
        $this->get(route('listing.receipts.download', ['type' => 'supplier', 'transaction' => $payment->id]))->assertNotFound();

        $attempt = app(ProviderPaystackService::class)->createAttempt($other, 'supplier', 'premium');
        $this->assertSame(ProviderPaymentTransaction::STATUS_INITIATED, $attempt->status);
        $this->get(PaymentReceipt::signedUrl($attempt))->assertNotFound();
    }
}
