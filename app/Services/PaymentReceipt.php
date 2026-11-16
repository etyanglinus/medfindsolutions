<?php

namespace App\Services;

use App\Models\ProviderPaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF receipt for a confirmed provider payment (Paystack or a fully discounted coupon plan).
 * Providers download theirs from the portal or from the signed link in the "payment received"
 * email; admins download any receipt from Provider payments.
 */
class PaymentReceipt
{
    /** Receipt number, stable for a payment: MFS-R-2026-000123 */
    public static function number(ProviderPaymentTransaction $payment): string
    {
        return 'MFS-R-'.($payment->paid_at ?? $payment->created_at)->format('Y').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    /** A link that keeps working without signing in (used in emails). */
    public static function signedUrl(ProviderPaymentTransaction $payment): string
    {
        return URL::signedRoute('provider.receipt.signed', ['transaction' => $payment->id]);
    }

    /** Paid payments of one provider account, newest first. */
    public function forClient(string $type, Model $client): Builder
    {
        return ProviderPaymentTransaction::query()->with('subscription')
            ->where('status', ProviderPaymentTransaction::STATUS_PAID)
            ->whereHas('providerProfile', fn ($q) => $q->where('provider_type', $type)->where('provider_id', $client->getKey()))
            ->latest('paid_at')->latest('id');
    }

    public function download(ProviderPaymentTransaction $payment): Response
    {
        abort_unless($payment->status === ProviderPaymentTransaction::STATUS_PAID, 404);
        $payment->loadMissing('subscription', 'providerProfile');
        $client = $payment->providerProfile?->provider;
        $setting = cache('setting');
        $pdf = Pdf::loadView('receipts.provider-payment', [
            'payment' => $payment,
            'subscription' => $payment->subscription,
            'client' => $client,
            'type' => $payment->providerProfile?->provider_type,
            'number' => self::number($payment),
            'appName' => $setting->app_name ?? config('app.name'),
        ])->setPaper('a4');
        return $pdf->download(self::number($payment).'.pdf');
    }
}
