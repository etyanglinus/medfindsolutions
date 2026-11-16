<?php
// Show entitlements values for all approved clinic subscriptions
$subs = \Modules\Doctor\app\Models\ClinicSubscription::where('status','approved')->get(['id','clinic_id','entitlements','expires_at','plan_code']);
foreach ($subs as $s) {
    $ent = is_null($s->getRawOriginal('entitlements')) ? 'NULL' : $s->getRawOriginal('entitlements');
    $exp = $s->expires_at ? $s->expires_at->toDateString() : 'no-expiry';
    echo "id={$s->id} clinic={$s->clinic_id} plan={$s->plan_code} expires={$exp} entitlements={$ent}\n";
}
