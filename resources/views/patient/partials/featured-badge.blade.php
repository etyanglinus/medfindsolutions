{{-- Shown on directory cards for providers on the top (Executive) plan. Expects $item with plan_tier from App\Support\PlanRanking. --}}
@if((int) ($item->plan_tier ?? 0) >= \App\Support\PlanRanking::FEATURED_TIER)
<span class="mf-featured-badge" title="{{ __('Featured listing — this provider is on our top visibility plan') }}"><i class="fas fa-star" aria-hidden="true"></i> {{ __('Featured') }}</span>
@once
<style>
.mf-featured-badge{position:absolute;top:10px;right:10px;z-index:2;display:inline-flex;align-items:center;gap:4px;background:#f5a524;color:#1f1300;font-size:.72rem;font-weight:700;line-height:1;padding:5px 9px;border-radius:999px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
.mf-featured-badge i{font-size:.7rem !important;width:auto !important;height:auto !important;color:inherit !important;margin:0 !important;opacity:1 !important}
.mf-featured-badge--inline{position:static;box-shadow:none;margin-bottom:6px}
</style>
@endonce
@endif
