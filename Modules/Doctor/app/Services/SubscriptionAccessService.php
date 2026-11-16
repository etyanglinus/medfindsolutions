<?php

namespace Modules\Doctor\app\Services;

use Modules\Doctor\app\Enums\DoctorStatus;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;

class SubscriptionAccessService {
    public function activeDoctorSubscription(Doctor $doctor): ?DoctorSubscription {
        return $doctor->subscriptions()
            ->where('status', 'approved')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query->whereNull('approved_at')->orWhere('approved_at', '<=', now()))
            ->orderByDesc('approved_at')->orderByDesc('id')
            ->first();
    }

    public function activeClinicSubscription(Clinic $clinic): ?ClinicSubscription {
        return $clinic->subscriptions()
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('approved_at')->orderByDesc('id')
            ->first();
    }

    public function canEditPortfolio(Doctor $doctor): bool {
        return $doctor->status == DoctorStatus::ACTIVE->value
            && $this->activeDoctorSubscription($doctor) !== null;
    }

    public function doctorCan(Doctor $doctor, string $entitlement): bool {
        return $this->activeDoctorSubscription($doctor)?->hasEntitlement($entitlement) ?? false;
    }

    public function clinicCan(Clinic $clinic, string $entitlement): bool {
        return $this->activeClinicSubscription($clinic)?->hasEntitlement($entitlement) ?? false;
    }
}
