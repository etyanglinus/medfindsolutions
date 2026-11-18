<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderPaymentTransaction;
use App\Services\ManualPaymentService;
use App\Services\ProviderPaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Doctor\app\Services\SubscriptionPlanService;

/** Staff actions for payments Paystack does not confirm: paybill review and direct plan activation. */
class ManualPaymentController extends Controller
{
    public function approve(Request $request, ProviderPaymentTransaction $transaction, ManualPaymentService $manual): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('client.payment.approve');
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);
        $manual->approve($transaction, Auth::guard('admin')->user(), $data['admin_note'] ?? null);
        return back()->with(['message' => __('Payment approved. The plan is active and the provider has been emailed their receipt.'), 'alert-type' => 'success']);
    }

    public function reject(Request $request, ProviderPaymentTransaction $transaction, ManualPaymentService $manual): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('client.payment.approve');
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => __('Tell the provider why the payment could not be confirmed.')]);
        $manual->reject($transaction, Auth::guard('admin')->user(), $data['reason']);
        return back()->with(['message' => __('Payment rejected. The provider has been emailed the reason and can submit a corrected code.'), 'alert-type' => 'success']);
    }

    /** Switch a plan on for any specialist, facility, school or supplier, including one whose plan has expired. */
    public function activate(Request $request, string $type, int $id, ManualPaymentService $manual): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('client.plan.activate');
        $client = ProviderPaystackService::model($type)::findOrFail($id);
        $codes = collect(app(SubscriptionPlanService::class)->for($type))->pluck('id')->all();
        $data = $request->validate([
            'package' => ['required', 'string', 'in:'.implode(',', $codes)],
            'amount' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'reference' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $payment = $manual->adminActivate($client, $type, $data['package'], (float) $data['amount'], $data['reference'] ?? null, $data['note'] ?? null, Auth::guard('admin')->user());
        $until = $payment->subscription?->expires_at?->format('d M Y');
        return back()->with(['message' => __(':name is active on :plan until :date.', ['name' => $client->name, 'plan' => $payment->subscription?->package_name, 'date' => $until]), 'alert-type' => 'success']);
    }
}
