<?php

use App\Http\Controllers\Provider\PartnerSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| MedFind exposes no public JSON API. The template's mobile-app API (patient/doctor apps,
| appointments, carts, payouts, Zoom, chat) was removed because MedFind does not offer those
| features. The only endpoint here receives Paystack's signed payment events.
*/

Route::post('provider-payments/paystack/webhook', [PartnerSubscriptionController::class, 'paystackWebhook'])
    ->middleware('throttle:120,1')
    ->name('api.provider-payments.paystack.webhook');
