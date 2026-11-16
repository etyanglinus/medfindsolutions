<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderSubscription;
use App\Models\SubscriptionCoupon;
use App\Services\PartnerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Modules\BasicPayment\app\Models\BasicPayment;

class ProviderPaymentReviewController extends Controller
{
    public function index(Request $request): View
    {
        checkAdminHasPermissionAndThrowException('client.payment.view');
        $payments = ProviderPaymentTransaction::query()->with(['providerProfile.provider', 'subscription'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()->paginate(25)->withQueryString();
        return view('admin.provider_payments.index', compact('payments'));
    }

    public function update(Request $request, ProviderPaymentTransaction $transaction): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('client.payment.refund');
        abort_unless($request->input('decision') === 'refund', 422, __('Paystack confirms payments automatically. Manual approval is disabled.'));
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'refund'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'refund_reference' => ['nullable', 'string', 'max:255'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['decision'] === 'approve' && data_get($transaction->subscription?->metadata, 'admin_onboarding')) {
            $request->validate(['payment_reference' => ['required', 'string', 'max:255']]);
        }
        if ($data['decision'] === 'refund') {
            abort_unless($transaction->gateway === 'paystack', 422, __('Only Paystack refunds are supported.'));
            checkAdminHasPermissionAndThrowException('setting.update');
            abort_unless($transaction->status === ProviderPaymentTransaction::STATUS_PAID, 422, __('Only completed payments can be refunded.'));
            if ($transaction->gateway === 'bank_transfer') {
                $request->validate(['refund_reference' => ['required', 'string', 'max:255'], 'admin_note' => ['required', 'string', 'max:1000']]);
                $refundStatus = ProviderPaymentTransaction::STATUS_REFUNDED;
                $refundPayload = ['refund_reference' => $data['refund_reference'], 'refund_note' => $data['admin_note'], 'refunded_at' => now()->toIso8601String(), 'refund_method' => 'manual'];
            } elseif ($transaction->gateway === 'paystack') {
                $secret = BasicPayment::where('key', 'paystack_secret_key')->value('value');
                abort_unless($secret, 503, __('Paystack is not configured for refunds.'));
                $response = Http::withToken($secret)->timeout(30)->post('https://api.paystack.co/refund', array_filter([
                    'transaction' => $transaction->gateway_reference,
                    'currency' => $transaction->currency,
                    'merchant_note' => $data['admin_note'] ?? __('Provider subscription refund'),
                ], fn ($value) => $value !== null && $value !== ''));
                abort_unless($response->successful() && $response->json('status') === true, 502, __('Paystack did not accept the refund request: :message', ['message' => $response->json('message') ?: __('Unknown gateway response')]));
                $gatewayRefund = $response->json('data', []);
                $refundStatus = data_get($gatewayRefund, 'status') === 'processed' ? ProviderPaymentTransaction::STATUS_REFUNDED : ProviderPaymentTransaction::STATUS_REFUND_PENDING;
                $refundPayload = ['refund' => $gatewayRefund, 'refund_note' => $data['admin_note'] ?? null, 'refund_requested_at' => now()->toIso8601String()];
            } else {
                abort(422, __('This payment gateway does not support refunds from this screen.'));
            }

            DB::transaction(function () use ($transaction, $data, $refundStatus, $refundPayload) {
                $locked = ProviderPaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === ProviderPaymentTransaction::STATUS_PAID, 422, __('This payment has already been updated.'));
                $subscription = ProviderSubscription::whereKey($locked->provider_subscription_id)->lockForUpdate()->first();
                if ($subscription && $subscription->status === ProviderSubscription::STATUS_APPROVED) {
                    if (data_get($subscription->metadata, 'admin_onboarding') && in_array($locked->providerProfile->provider_type, ['doctor', 'clinic'])) {
                        $legacy = $locked->providerProfile->provider_type === 'doctor' ? \Modules\Doctor\app\Models\DoctorSubscription::class : \Modules\Doctor\app\Models\ClinicSubscription::class;
                        $legacy::where('transaction_code', $locked->gateway_reference)->update(['status' => 'rejected']);
                    }
                    $metadata = $subscription->metadata ?? [];
                    $metadata['pre_refund_status'] = $subscription->status;
                    $subscription->update(['status' => ProviderSubscription::STATUS_CANCELLED, 'cancelled_at' => now(), 'metadata' => $metadata]);
                }
                $locked->update(['status' => $refundStatus, 'gateway_payload' => array_merge($locked->gateway_payload ?? [], $refundPayload)]);
            });
            return back()->with(['message' => $refundStatus === ProviderPaymentTransaction::STATUS_REFUNDED ? __('Refund recorded and subscription entitlements revoked.') : __('Refund request accepted and is awaiting gateway processing. Subscription entitlements were revoked.'), 'alert-type' => 'success']);
        }
        abort_unless($transaction->gateway === 'bank_transfer', 422, __('Only bank transfer records can be approved or rejected manually.'));
        DB::transaction(function () use ($transaction, $data) {
            $transaction = ProviderPaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            abort_unless($transaction->status === ProviderPaymentTransaction::STATUS_PENDING, 422, __('This payment has already been reviewed.'));
            $subscription = ProviderSubscription::whereKey($transaction->provider_subscription_id)->lockForUpdate()->firstOrFail();
            abort_unless($subscription->provider_profile_id === $transaction->provider_profile_id && $subscription->status === ProviderSubscription::STATUS_PENDING, 422);
            if ($data['decision'] === 'approve') {
                if (!empty($data['payment_reference'])) {
                    abort_if(ProviderPaymentTransaction::where('gateway_reference', $data['payment_reference'])->where('id', '!=', $transaction->id)->exists(), 422, __('This payment reference has already been used.'));
                    $transaction->gateway_reference = $data['payment_reference'];
                }
                abort_unless((float) $transaction->amount === (float) $subscription->amount && $transaction->currency === $subscription->currency, 422, __('The recorded payment does not match the subscription amount.'));
                $profile = $transaction->providerProfile;
                $current = app(PartnerPortalService::class)->subscription($profile);
                $starts = $current?->expires_at && $current->expires_at->isFuture() ? $current->expires_at->copy() : now();
                $subscription->update(['status' => ProviderSubscription::STATUS_APPROVED, 'approved_at' => now(), 'starts_at' => $starts, 'expires_at' => $starts->copy()->addMonths($subscription->duration_months)]);
                $transaction->update(['status' => ProviderPaymentTransaction::STATUS_PAID, 'paid_at' => now(), 'gateway_payload' => array_merge($transaction->gateway_payload ?? [], ['admin_approved_at' => now()->toIso8601String(), 'confirmed_by' => auth('admin')->id(), 'admin_note' => $data['admin_note'] ?? null])]);
                if ($profile->status === \App\Models\ProviderProfile::STATUS_PENDING_PAYMENT) $profile->update(['status' => \App\Models\ProviderProfile::STATUS_PENDING_APPROVAL]);
            } else {
                $metadata = $subscription->metadata ?? [];
                if (! empty($metadata['coupon_consumed']) && ! empty($metadata['coupon_code'])) {
                    $coupon = SubscriptionCoupon::whereRaw('upper(code) = ?', [strtoupper($metadata['coupon_code'])])->lockForUpdate()->first();
                    if ($coupon && $coupon->usage_count > 0) $coupon->decrement('usage_count');
                    $metadata['coupon_consumed'] = false;
                }
                $subscription->update(['status' => ProviderSubscription::STATUS_REJECTED, 'metadata' => array_merge($metadata, ['admin_note' => $data['admin_note'] ?? null])]);
                $transaction->update(['status' => ProviderPaymentTransaction::STATUS_FAILED, 'failure_reason' => $data['admin_note'] ?? __('Bank transfer was rejected.')]);
            }
        });
        return back()->with(['message' => __('Payment review saved. Publication and verification remain separate decisions.'), 'alert-type' => 'success']);
    }
}
