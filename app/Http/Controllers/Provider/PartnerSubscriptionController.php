<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Services\PartnerPortalService;
use App\Services\SubscriptionCouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class PartnerSubscriptionController extends Controller
{
    public function paystackWebhook(Request $request): JsonResponse {
    $secret = BasicPayment::where("key", "paystack_secret_key")->value("value");
    abort_unless($secret && hash_equals(hash_hmac("sha512", $request->getContent(), $secret), $request->header("x-paystack-signature", "")), 401);
    if ($request->input("event") !== "charge.success") return response()->json(["ignored" => true]);
    $data = $request->validate(["data.reference" => ["required", "string"], "data.status" => ["required", Rule::in(["success"])], "data.amount" => ["required", "integer", "min:0"], "data.currency" => ["required", "string"], "data.customer.email" => ["required", "email"]])["data"];
    try {
        $duplicate = app(\App\Services\ProviderPaystackService::class)->settle($data);
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        // Amount/email/plan mismatch: keep the attempt pending and leave a trail for admin review.
        \Illuminate\Support\Facades\Log::warning('Paystack webhook payment rejected', ['reference' => $data['reference'] ?? null, 'error' => $e->getMessage()]);
        throw $e;
    }
    return response()->json($duplicate === null ? ["ignored" => true] : ["success" => true, "duplicate" => $duplicate]);
}

    public function __construct(
        private SubscriptionPlanService $plans,
        private PartnerPortalService $portal,
    ) {}

    // ─────────────────────────────────────────────────────────
    //  Initial sign-up subscription (token-based, unauthenticated)
    // ─────────────────────────────────────────────────────────

    public function show(string $type, string $token): View
    {
        $definition = PartnerAuthController::definition($type);
        $record = $definition['model']::where('registration_token', $token)->firstOrFail();
        $profile = ProviderProfile::where('provider_type', $definition['profile_type'])->where('provider_id', $record->id)->firstOrFail();
        $packages = $this->plans->for($type);
        $paystackPublicKey = BasicPayment::where('key', 'paystack_public_key')->value('value');
        $paystackEnabled = BasicPayment::where('key', 'paystack_status')->value('value') === 'active';

        return view('patient.profile.auth.partner-subscription', compact('type', 'record', 'profile', 'packages', 'paystackPublicKey', 'paystackEnabled'));
    }

    public function storeBankTransfer(Request $request, string $type, string $token): RedirectResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function verifyPaystack(Request $request, string $type, string $token): JsonResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    // ─────────────────────────────────────────────────────────
    //  Renewal (authenticated partner — school / supplier)
    // ─────────────────────────────────────────────────────────

    public function renewal(string $type): View
    {
        $definition = PartnerAuthController::definition($type);
        $record = auth($definition['guard'])->user();
        abort_unless($record, 403);

        $profile  = ProviderProfile::where('provider_type', $definition['profile_type'])
            ->where('provider_id', $record->id)
            ->firstOrFail();
        $packages     = $this->plans->for($type);
        $subscription = $this->portal->subscription($profile);
        $history      = $profile->subscriptions()->with('payments')->latest()->paginate(10);

        $paystackPublicKey = BasicPayment::where('key', 'paystack_public_key')->value('value');
        $paystackEnabled   = BasicPayment::where('key', 'paystack_status')->value('value') === 'active';

        return view('patient.profile.auth.partner-renewal', compact(
            'type', 'record', 'profile', 'packages', 'subscription',
            'history', 'paystackPublicKey', 'paystackEnabled'
        ));
    }

    public function storeRenewalBankTransfer(Request $request, string $type): RedirectResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function verifyRenewalPaystack(Request $request, string $type): JsonResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }
}
