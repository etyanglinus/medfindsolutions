<?php

namespace App\Services;

use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Lets people sign up again with an email whose earlier sign-up was never finished.
 *
 * A provider account has to exist before payment (Paystack needs something to attach the payment
 * to), so abandoned sign-ups used to keep the email forever and the person got "email already
 * exists" on every retry. Before a new registration is validated, this service looks for accounts
 * using the same email and:
 *   - releases any that are unfinished (never paid, never activated) or already deleted, by renaming
 *     their email/slug (history is kept for admins) so the new sign-up can proceed;
 *   - first asks Paystack about any payment still in progress, so a payment is never orphaned;
 *   - keeps real accounts protected, with a message that points to login / password reset.
 */
class UnfinishedSignupService
{
    private const IN_FLIGHT_MINUTES = 30;

    /** @throws ValidationException when the email belongs to an account that must not be replaced */
    public function clearForRegistration(string $type, ?string $email): void
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

        [$model, $column] = $this->definition($type);
        $query = $model::query();
        if (method_exists($model, 'bootSoftDeletes')) $query->withTrashed();

        foreach ($query->whereRaw("lower($column) = ?", [$email])->get() as $account) {
            $profile = ProviderProfile::withTrashed()->where('provider_type', $type)->where('provider_id', $account->id)->first();

            if (!$this->isTrashed($account)) {
                if ($this->isFinished($type, $account, $profile)) {
                    throw ValidationException::withMessages(['email' => $this->existingAccountMessage($type, $profile)]);
                }
                if ($profile && $this->paymentStillInProgress($profile)) {
                    throw ValidationException::withMessages(['email' => __('A payment for this email is still being processed by Paystack. Please wait a few minutes and try again. If you already paid, you will receive an email when it is confirmed.')]);
                }
            }

            $this->release($type, $account, $profile, $column);
        }
    }

    private function definition(string $type): array
    {
        return match ($type) {
            'doctor' => [\Modules\Doctor\app\Models\Doctor::class, 'email'],
            'clinic' => [\Modules\Doctor\app\Models\Clinic::class, 'email'],
            'school' => [\Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class, 'official_email'],
            'supplier' => [\Modules\MedicalSupplier\app\Models\MedicalSupplier::class, 'official_email'],
            default => abort(404),
        };
    }

    private function isTrashed(Model $account): bool
    {
        return method_exists($account, 'trashed') && $account->trashed();
    }

    /** Paid, activated, or prepared by an admin: a real account that a new sign-up must not replace. */
    private function isFinished(string $type, Model $account, ?ProviderProfile $profile): bool
    {
        if (in_array($type, ['doctor', 'clinic'], true)) {
            if ((int) $account->status === 1) return true;
            if ($account->subscriptions()->where('status', 'approved')->exists()) return true;
        }
        if (!$profile) return false;
        if ($profile->paymentTransactions()->where('status', ProviderPaymentTransaction::STATUS_PAID)->exists()) return true;
        if ($profile->subscriptions()->where('status', 'approved')->exists()) return true;
        if (in_array($profile->status, ['published', 'approved', 'pending_approval'], true)) return true;
        return $profile->subscriptions()->where('status', 'pending')->get()
            ->contains(fn ($subscription) => data_get($subscription->metadata, 'admin_onboarding'));
    }

    /** Checks recent unfinished Paystack attempts with Paystack before the account is released. */
    private function paymentStillInProgress(ProviderProfile $profile): bool
    {
        $attempts = $profile->paymentTransactions()->where('gateway', 'paystack')->whereIn('status', ['initiated', 'pending'])->get();
        foreach ($attempts as $attempt) {
            try {
                app(ProviderPaystackService::class)->reconcile($attempt);
            } catch (\Throwable $e) {
                Log::warning('Could not check Paystack before releasing a sign-up', ['payment' => $attempt->id, 'error' => $e->getMessage()]);
                if ($attempt->created_at?->gt(now()->subMinutes(self::IN_FLIGHT_MINUTES))) return true;
                continue;
            }
            $attempt->refresh();
            if ($attempt->status === ProviderPaymentTransaction::STATUS_PAID) return true;
            if (in_array($attempt->status, ['initiated', 'pending'], true) && $attempt->created_at?->gt(now()->subMinutes(self::IN_FLIGHT_MINUTES))) return true;
        }
        return false;
    }

    private function existingAccountMessage(string $type, ?ProviderProfile $profile): string
    {
        $adminPrepared = $profile && $profile->subscriptions()->where('status', 'pending')->get()->contains(fn ($s) => data_get($s->metadata, 'admin_onboarding'));
        if ($adminPrepared) {
            return __('Medfind Solutions has already set up an account for this email. Please use the payment link we emailed you, or contact us.');
        }
        return __('An account with this email already exists. Please log in, or use "Forgot password" if you cannot remember your password.');
    }

    /** Frees the email (and slug) of an unfinished or deleted account while keeping its history. */
    private function release(string $type, Model $account, ?ProviderProfile $profile, string $column): void
    {
        DB::transaction(function () use ($type, $account, $profile, $column) {
            $suffix = '#released-'.$account->id.'-'.now()->format('YmdHis');
            if ($profile) {
                $profile->paymentTransactions()->whereIn('status', ['initiated', 'pending'])
                    ->update(['status' => ProviderPaymentTransaction::STATUS_FAILED, 'failure_reason' => 'Replaced by a new sign-up with the same email.']);
                $profile->subscriptions()->where('status', 'pending')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            }
            if (in_array($type, ['doctor', 'clinic'], true)) {
                $account->subscriptions()->where('status', 'pending')->update(['status' => 'rejected']);
            }

            $account->forceFill([
                $column => mb_substr($account->{$column}.$suffix, 0, 255),
                'slug' => mb_substr($account->slug.'-released-'.$account->id, 0, 255),
                'registration_token' => null,
                'email_verified_token' => null,
            ] + (in_array($type, ['doctor', 'clinic'], true) ? ['status' => 0] : []))->saveQuietly();

            if (method_exists($account, 'bootSoftDeletes') && !$account->trashed()) $account->delete();
            if ($profile && !$profile->trashed()) {
                $profile->update(['status' => 'suspended', 'published_at' => null]);
                $profile->delete();
            }
        });
        Log::info('Released unfinished sign-up for re-registration', ['type' => $type, 'account' => $account->id]);
    }
}
