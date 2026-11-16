<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class ClientOnboardingController extends Controller
{
    public function create(Request $request, SubscriptionPlanService $plans)
    {
        $type = $request->input('type', 'doctor');
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier']), 404);
        $group = $type === 'clinic' ? 'facility' : $type;
        checkAdminHasPermissionAndThrowException("$group.create");
        return view('admin.clients.create', [
            'type' => $type, 'plans' => $plans->for($type),
            'departments' => \Modules\Doctor\app\Models\Department::all(),
            'locations' => \Modules\Doctor\app\Models\Location::all(),
        ]);
    }

    public function store(Request $request, SubscriptionPlanService $plans)
    {
        $type = $request->validate(['type' => ['required', Rule::in(['doctor', 'clinic', 'school', 'supplier'])]])['type'];
        $group = $type === 'clinic' ? 'facility' : $type;
        checkAdminHasPermissionAndThrowException("$group.store");
        $model = match ($type) {
            'doctor' => \Modules\Doctor\app\Models\Doctor::class,
            'clinic' => \Modules\Doctor\app\Models\Clinic::class,
            'school' => \Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class,
            'supplier' => \Modules\MedicalSupplier\app\Models\MedicalSupplier::class,
        };
        $emailColumn = in_array($type, ['doctor', 'clinic']) ? 'email' : 'official_email';
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique((new $model)->getTable(), $emailColumn)],
            'phone' => ['required', 'string', 'max:40'],
            'package' => ['required', Rule::in(array_column($plans->for($type), 'id'))],
            'department_id' => [$type === 'doctor' ? 'required' : 'nullable', 'exists:departments,id'],
            'location_id' => [$type === 'doctor' ? 'required' : 'nullable', 'exists:locations,id'],
        ]);
        $plan = $plans->find($type, $data['package']);
        $paystack = app(\App\Services\ProviderPaystackService::class);
        $paystack->secret();
        if ($plan['price'] <= 0) throw \Illuminate\Validation\ValidationException::withMessages(['package' => __('Choose a paid plan for Paystack checkout.')]);
        $payment = DB::transaction(function () use ($type, $model, $emailColumn, $data, $plan) {
            $password = Str::password(20);
            $attributes = ['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(10)),
                $emailColumn => $data['email'], 'phone' => $data['phone'], 'password' => Hash::make($password), 'registration_token' => Str::random(80)];
            if (in_array($type, ['doctor', 'clinic'])) $attributes['status'] = 0;
            if ($type === 'doctor') $attributes += ['department_id' => $data['department_id'], 'location_id' => $data['location_id'], 'show_homepage' => 0];
            $client = $model::create($attributes);
            $profile = ProviderProfile::create(['provider_type' => $type, 'provider_id' => $client->id, 'status' => ProviderProfile::STATUS_PENDING_PAYMENT]);
            if (in_array($type, ['school', 'supplier'])) $client->update(['provider_profile_id' => $profile->id]);
            $subscription = ProviderSubscription::create([
                'provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'],
                'amount' => $plan['price'], 'currency' => 'KES', 'duration_months' => $plan['duration_months'], 'status' => 'pending',
                'metadata' => ['entitlements' => $plan['entitlements'], 'admin_onboarding' => true, 'created_by' => auth('admin')->id(), 'login_secret' => Crypt::encryptString($password)],
            ]);
            $reference = 'MFS-'.Str::uuid();
            return ProviderPaymentTransaction::create([
                'provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id,
                'gateway' => 'paystack', 'gateway_reference' => $reference, 'idempotency_key' => 'paystack:'.$reference,
                'amount' => $plan['price'], 'currency' => 'KES', 'status' => 'initiated',
                'gateway_payload' => ['admin_onboarding' => true, 'expected_email' => $data['email']],
            ]);
        });
        try {
            $url = $paystack->initialize($payment);
            \Illuminate\Support\Facades\Mail::to($data['email'])->queue(new \App\Mail\ClientPaymentInvitation($data['name'], $url));
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with(['message' => __('Account created but checkout or invitation delivery failed. Retry Paystack checkout from Client Payment Reviews. The account is still inactive.'), 'alert-type' => 'warning']);
        }
        return back()->with(['message' => __('Client created and Paystack payment invitation queued. Paystack confirmation automatically activates the account and emails login details.'), 'alert-type' => 'success', 'paystack_checkout_url' => $url]);
    }
}
