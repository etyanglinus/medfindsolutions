<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\ProviderInquiry;
use Modules\Doctor\app\Models\ProviderReview;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class ProviderEngagementController extends Controller {
    public function storeInquiry(Request $request, string $type, int $provider): RedirectResponse {
        $model = $this->provider($type, $provider);
        abort_unless(app(SubscriptionAccessService::class)->{$type . 'Can'}($model, 'inquiries'), 404);
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'], 'reason' => ['required', 'string', 'max:3000'],
            'preferred_contact_method' => ['required', 'in:phone,whatsapp,email'],
        ]);
        ProviderInquiry::create($data + ['provider_type' => $type, 'provider_id' => $model->id]);
        try {
            Mail::raw(__('A new patient inquiry was received. Sign in to your MedFind dashboard to view it.'), fn ($mail) => $mail->to($model->email)->subject(__('New MedFind patient inquiry')));
        } catch (\Throwable $exception) {
            Log::warning('Provider inquiry email could not be sent: ' . $exception->getMessage());
        }
        return back()->with(['message' => __('Your inquiry has been sent.'), 'alert-type' => 'success']);
    }

    public function storeReview(Request $request, string $type, int $provider): RedirectResponse {
        $model = $this->provider($type, $provider);
        abort_unless(app(SubscriptionAccessService::class)->{$type . 'Can'}($model, 'reviews'), 404);
        abort_if($type === 'doctor' && $model->reviews_enabled === false, 404);
        $data = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:255'], 'reviewer_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'], 'review' => ['required', 'string', 'max:3000'],
        ]);
        ProviderReview::create($data + ['provider_type' => $type, 'provider_id' => $model->id]);
        return back()->with(['message' => __('Thank you for your review.'), 'alert-type' => 'success']);
    }

    private function provider(string $type, int $id): Doctor|Clinic {
        abort_unless(in_array($type, ['doctor', 'clinic'], true), 404);
        return $type === 'doctor' ? Doctor::active()->findOrFail($id) : Clinic::active()->findOrFail($id);
    }
}
