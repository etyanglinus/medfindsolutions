<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * "Renew now" links for emails. The link is signed and opens the provider's plan page without a
 * login, where they choose a plan and pay with Paystack. Renewing early never loses paid days:
 * a new plan starts when the current one ends.
 */
class ProviderRenewalLinks
{
    public const TYPES = ['doctor', 'clinic', 'school', 'supplier'];

    public function url(string $type, Model $client, int $validDays = 30): string
    {
        return URL::temporarySignedRoute('provider.renew-link', now()->addDays($validDays), ['type' => $type, 'id' => $client->id]);
    }

    /** The plan-and-pay page that works without logging in (keyed by the account's registration token). */
    public function planPageUrl(string $type, Model $client): string
    {
        if (!$client->registration_token) {
            $client->forceFill(['registration_token' => Str::random(80)])->saveQuietly();
        }
        return match ($type) {
            'doctor' => route('doctor.subscription.complete', ['token' => $client->registration_token]),
            'clinic' => route('clinic.subscription.complete', ['token' => $client->registration_token]),
            'school', 'supplier' => route('website.partner.subscription', ['type' => $type, 'token' => $client->registration_token]),
        };
    }

    public static function email(Model $client): ?string
    {
        return $client->email ?? $client->official_email ?? null;
    }
}
