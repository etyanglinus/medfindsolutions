<?php

namespace App\Http\Controllers;

use App\Models\ProviderPaymentTransaction;
use App\Services\ListingInsightsService;
use App\Services\PartnerPortalService;
use App\Services\PaymentReceipt;

class ProviderReceiptController extends Controller
{
    public function __construct(private PaymentReceipt $receipts) {}

    /** "Receipts" page in the specialist, facility, school and supplier portals. */
    public function index(string $type, ListingInsightsService $service)
    {
        $record = $service->owner($type);
        $payments = $this->receipts->forClient($type, $record)->paginate(20);
        $layout = match ($type) {'doctor' => 'doctor.master_layout', 'clinic' => 'clinic.master_layout', default => 'provider.partner-layout'};
        $contentSection = match ($type) {'doctor' => 'admin-content', 'clinic' => 'clinic-content', default => 'partner-content'};
        $profile = $record->providerProfile ?? null;
        $subscription = $profile && in_array($type, ['school', 'supplier']) ? app(PartnerPortalService::class)->subscription($profile) : null;
        $entitlements = $profile && in_array($type, ['school', 'supplier']) ? app(PartnerPortalService::class)->entitlements($profile) : [];
        return view('provider.receipts', compact('type', 'payments', 'layout', 'contentSection', 'record', 'profile', 'subscription', 'entitlements'));
    }

    public function download(string $type, ProviderPaymentTransaction $transaction, ListingInsightsService $service)
    {
        $record = $service->owner($type);
        abort_unless($this->receipts->forClient($type, $record)->whereKey($transaction->id)->exists(), 404);
        return $this->receipts->download($transaction);
    }

    /** Signed link from the payment email. */
    public function signed(ProviderPaymentTransaction $transaction)
    {
        return $this->receipts->download($transaction);
    }

    public function admin(ProviderPaymentTransaction $transaction)
    {
        checkAdminHasPermissionAndThrowException('client.payment.view');
        return $this->receipts->download($transaction);
    }
}
