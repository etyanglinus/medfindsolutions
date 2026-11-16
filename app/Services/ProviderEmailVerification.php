<?php

namespace App\Services;

use App\Traits\GlobalMailTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Specialists and facilities must confirm their email before signing in (schools and suppliers
 * already had to). When an unverified provider signs in with the right password, a fresh link is
 * emailed automatically, at most once every 10 minutes per account.
 */
class ProviderEmailVerification
{
    use GlobalMailTrait;

    public const RESEND_MINUTES = 10;

    /** @return bool true when a new link was sent now, false when one was sent recently */
    public function sendLink(string $type, Model $provider): bool
    {
        $key = "email-verification-sent:$type:{$provider->id}";
        if (Cache::has($key)) return false;

        $provider->forceFill(['email_verified_token' => Str::random(100)])->save();
        $url = $type === 'doctor' ? route('doctor.verification', $provider->email_verified_token) : route('clinic.verification', $provider->email_verified_token);
        try {
            [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $provider->name]);
            $this->sendMail($provider->email, $subject, $message, [__('CONFIRM YOUR EMAIL') => $url], true);
        } catch (\Throwable $e) {
            Log::warning('Verification email failed', ['type' => $type, 'id' => $provider->id, 'error' => $e->getMessage()]);
            return false;
        }
        Cache::put($key, true, now()->addMinutes(self::RESEND_MINUTES));
        return true;
    }

    public function message(string $email, bool $sentNow): string
    {
        return $sentNow
            ? __('Please confirm your email address first. We have just sent a confirmation link to :email.', ['email' => $email])
            : __('Please confirm your email address first using the link we sent to :email a few minutes ago. Check your spam folder.', ['email' => $email]);
    }
}
