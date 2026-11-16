<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Orders directory listings so providers on higher plans appear first:
 *   Executive (3) > Premium (2) > Basic / Standard / other paid plan (1) > none (0).
 * Only approved, started and unexpired subscriptions count.
 *
 * Inside each plan level the order rotates once a day (deterministic, so pagination stays stable
 * for the whole day), so every provider on the same plan gets a fair turn at the top.
 * Visitor filters (keyword, specialty, location, ...) are applied by the caller first, so
 * paid placement never pushes a non-matching provider into a search.
 *
 * Adds a `plan_tier` column to each row, used by the views for the "Featured" label.
 */
class PlanRanking
{
    public const FEATURED_TIER = 3;

    public static function apply(Builder $query, string $type): Builder
    {
        $table = $query->getModel()->getTable();
        $now = now()->toDateTimeString();
        $tier = "max(case when lower(s.plan_code) like '%executive%' then 3"
            ." when lower(s.plan_code) like '%premium%' or lower(s.plan_code) like '%platinum%' then 2"
            ." when s.plan_code is not null and s.plan_code <> '' then 1 else 0 end)";

        [$sql, $bindings] = match ($type) {
            'doctor', 'clinic' => [
                "select coalesce($tier, 0) from {$type}_subscriptions s where s.{$type}_id = {$table}.id and s.status = 'approved'"
                ." and (s.approved_at is null or s.approved_at <= ?) and (s.expires_at is null or s.expires_at > ?)",
                [$now, $now],
            ],
            'school', 'supplier' => [
                "select coalesce($tier, 0) from provider_subscriptions s where s.provider_profile_id = {$table}.provider_profile_id and s.status = 'approved'"
                ." and (s.starts_at is null or s.starts_at <= ?) and (s.expires_at is null or s.expires_at > ?)",
                [$now, $now],
            ],
        };

        $seed = (int) now()->format('z') * 7919 + (int) now()->format('Y');

        if (is_null($query->getQuery()->columns)) $query->select("{$table}.*");

        return $query
            ->selectRaw("({$sql}) as plan_tier", $bindings)
            ->reorder()
            ->orderByDesc('plan_tier')
            ->orderByRaw("(({$table}.id * 7919 + ?) % 10007)", [$seed])
            ->orderBy("{$table}.id");
    }
}
