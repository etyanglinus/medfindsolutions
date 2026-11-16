<?php

namespace App\Services;

use App\Models\AccountCreationAudit;
use App\Models\Admin;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\ProviderDocument;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\SupplierProduct;

class AdminDashboardService
{
    public function data(Request $request): array
    {
        $definitions = [
            'doctor' => ['label' => 'Specialists', 'model' => Doctor::class, 'permission' => 'doctor.view', 'url' => route('admin.doctor.index'), 'color' => '#3269b2', 'icon' => 'fa-user-md'],
            'clinic' => ['label' => 'Facilities', 'model' => Clinic::class, 'permission' => 'facility.view', 'url' => route('admin.clinic-listing.index'), 'color' => '#208768', 'icon' => 'fa-hospital'],
            'school' => ['label' => 'Schools', 'model' => HealthcareInstitution::class, 'permission' => 'school.view', 'url' => route('admin.provider-profiles.index', ['type' => 'school']), 'color' => '#7254b5', 'icon' => 'fa-graduation-cap'],
            'supplier' => ['label' => 'Suppliers', 'model' => MedicalSupplier::class, 'permission' => 'supplier.view', 'url' => route('admin.provider-profiles.index', ['type' => 'supplier']), 'color' => '#c17625', 'icon' => 'fa-boxes'],
        ];
        $definitions = array_filter($definitions, fn ($definition) => checkAdminHasPermission($definition['permission']));
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys($definitions))], 'period' => ['nullable', Rule::in(['7', '30', '90', '365'])],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $to = !empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->endOfDay();
        $from = !empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $to->copy()->subDays((int) ($filters['period'] ?? 30) - 1)->startOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 366) throw \Illuminate\Validation\ValidationException::withMessages(['from' => __('Choose a date range of up to one year.')]);
        $selected = !empty($filters['type']) ? array_intersect_key($definitions, [$filters['type'] => true]) : $definitions;
        $types = array_keys($selected);
        $monthly = $from->diffInDays($to) > 90;
        $keyFormat = $monthly ? 'Y-m' : 'Y-m-d';
        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy();
        $buckets = [];
        while ($cursor->lte($to)) {
            $buckets[$cursor->format($keyFormat)] = $cursor->format($monthly ? 'M Y' : 'd M');
            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }
        $series = function ($rows) use ($buckets, $keyFormat) {
            $values = array_fill_keys(array_keys($buckets), 0);
            foreach ($rows as $row) {
                $key = Carbon::parse($row->day)->format($keyFormat);
                if (array_key_exists($key, $values)) $values[$key] += (float) $row->total;
            }
            return array_values($values);
        };
        $canFinance = checkAdminHasPermission('client.payment.view') || checkAdminHasPermission('payment.view');
        $canCreators = checkAdminHasPermission('account.report.view');
        $sharedPayments = ProviderPaymentTransaction::whereHas('providerProfile', fn ($q) => $q->whereIn('provider_type', $types));
        $sharedSubscriptions = ProviderSubscription::whereHas('providerProfile', fn ($q) => $q->whereNull('deleted_at')->whereIn('provider_type', $types));
        $providerCards = []; $growth = []; $revenue = []; $health = []; $recent = collect(); $queues = [];
        foreach ($selected as $type => $definition) {
            $model = $definition['model'];
            $count = $model::count();
            $new = $model::whereBetween('created_at', [$from, $to])->count();
            $providerCards[] = ['type' => $type, 'label' => __($definition['label']), 'count' => $count, 'new' => $new, 'url' => $definition['url'], 'color' => $definition['color'], 'icon' => $definition['icon']];
            $daily = $model::selectRaw('DATE(created_at) as day, COUNT(*) as total')->whereBetween('created_at', [$from, $to])->groupByRaw('DATE(created_at)')->get();
            $growth[] = ['label' => __($definition['label']), 'data' => $series($daily), 'borderColor' => $definition['color'], 'backgroundColor' => $definition['color'].'22', 'fill' => true, 'tension' => .35];
            $subscriptions = (clone $sharedSubscriptions)->whereHas('providerProfile', fn ($q) => $q->where('provider_type', $type));
            $activeProfileIds = (clone $subscriptions)->where('status', 'approved')->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->pluck('provider_profile_id');
            $active = ProviderProfile::whereIn('id', $activeProfileIds)->pluck('provider_id')->all();
            $expired = (clone $subscriptions)->whereIn('status', ['approved', 'expired'])->where('expires_at', '<=', now())->distinct()->count('provider_profile_id');
            $pending = (clone $subscriptions)->where('status', 'pending')->count();
            $expiring = (clone $subscriptions)->where('status', 'approved')->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->whereBetween('expires_at', [now(), now()->addDays(30)])->count();
            $legacy = in_array($type, ['doctor', 'clinic']) ? $this->legacy($type) : null;
            if ($legacy) {
                $active = array_merge($active, (clone $legacy)->where('status', 'approved')->where('approved_at', '<=', now())->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->pluck($type.'_id')->all());
                $pending += (clone $legacy)->where('status', 'pending')->count();
                $expired += (clone $legacy)->where('status', 'approved')->where('expires_at', '<=', now())->distinct()->count($type.'_id');
                $expiring += (clone $legacy)->where('status', 'approved')->where('approved_at', '<=', now())->whereBetween('expires_at', [now(), now()->addDays(30)])->count();
            }
            $health[] = ['type' => $type, 'label' => __($definition['label']), 'active' => count(array_unique($active)), 'pending' => $pending, 'expired' => $expired, 'expiring' => $expiring];
            if ($canFinance) {
                $payments = (clone $sharedPayments)->whereHas('providerProfile', fn ($q) => $q->where('provider_type', $type))->where('status', 'paid')->where('currency', 'KES')->whereBetween('paid_at', [$from, $to]);
                $points = $series((clone $payments)->selectRaw('DATE(paid_at) as day, SUM(amount) as total')->groupByRaw('DATE(paid_at)')->get());
                if ($legacy) {
                    $legacyPoints = $series($this->legacy($type, true)->where('status', 'approved')->whereBetween('approved_at', [$from, $to])->selectRaw('DATE(approved_at) as day, SUM(package_price) as total')->groupByRaw('DATE(approved_at)')->get());
                    $points = array_map(fn ($a, $b) => $a + $b, $points, $legacyPoints);
                }
                $revenue[] = ['label' => __($definition['label']), 'data' => $points, 'borderColor' => $definition['color'], 'backgroundColor' => $definition['color'].'22', 'fill' => true, 'tension' => .35];
            }
            foreach ($model::latest()->limit(8)->get() as $client) {
                $profile = ProviderProfile::where('provider_type', $type)->where('provider_id', $client->id)->first();
                $subscription = $profile?->subscriptions()->latest()->first();
                if (!$subscription && $legacy) $subscription = $client->subscriptions()->latest()->first();
                $audit = $canCreators ? AccountCreationAudit::where('provider_type', $type)->where('provider_id', $client->id)->first() : null;
                $recent->push(['name' => $client->name, 'email' => $client->email ?? $client->official_email, 'type' => __($definition['label']), 'date' => $client->created_at, 'plan' => $subscription?->package_name, 'payment' => $subscription?->status ?? 'not selected', 'source' => $audit?->source ?? 'unknown', 'creator' => $audit?->creator_name, 'url' => $definition['url']]);
            }
            if ($legacy) $queues[] = ['label' => __($definition['label']).' '.__('payment approvals'), 'count' => (clone $legacy)->where('status', 'pending')->count(), 'url' => route('admin.subscriptions.'.$type.'.index', ['status' => 'pending']), 'icon' => 'fa-credit-card'];
            if (in_array($type, ['school', 'supplier'])) $queues[] = ['label' => __($definition['label']).' '.__('awaiting approval'), 'count' => ProviderProfile::where('provider_type', $type)->whereIn('status', ['draft', 'pending_approval', 'changes_required'])->count(), 'url' => route('admin.provider-profiles.index', ['type' => $type, 'status' => 'pending']), 'icon' => $definition['icon']];
        }
        if (checkAdminHasPermission('client.payment.view')) $queues[] = ['label' => __('Client payment reviews'), 'count' => (clone $sharedPayments)->whereHas('providerProfile', fn ($q) => $q->whereNull('deleted_at'))->whereIn('status', ['pending', 'initiated'])->count(), 'url' => route('admin.provider-payments.index', ['status' => 'pending']), 'icon' => 'fa-receipt'];
        if (checkAdminHasPermission('doctor.view')) $queues[] = ['label' => __('Verification documents'), 'count' => ProviderDocument::whereIn('provider_type', $types)->where('status', 'pending')->count(), 'url' => route('admin.provider-documents.index', ['provider_type' => $types[0] ?? 'doctor']), 'icon' => 'fa-file-alt'];
        if (isset($selected['supplier'])) $queues[] = ['label' => __('Supplier products awaiting review'), 'count' => SupplierProduct::whereHas('supplier')->whereIn('approval_status', ['pending', 'changes_required'])->count(), 'url' => route('admin.supplier-listings.index'), 'icon' => 'fa-boxes'];
        $sources = []; $staff = [];
        if ($canCreators) {
            $audits = AccountCreationAudit::whereIn('provider_type', $types)->whereBetween('created_at', [$from, $to]);
            $sources = (clone $audits)->selectRaw('source, COUNT(*) as total')->groupBy('source')->pluck('total', 'source')->all();
            $creations = (clone $audits)->whereNotNull('creator_admin_id')->selectRaw('creator_admin_id, MAX(creator_name) as name, COUNT(*) as total')->groupBy('creator_admin_id')->get()->keyBy('creator_admin_id');
            $confirmations = $canFinance ? (clone $sharedPayments)->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->selectRaw("JSON_EXTRACT(gateway_payload, '$.confirmed_by') as creator_id, COUNT(*) as total")->groupByRaw("JSON_EXTRACT(gateway_payload, '$.confirmed_by')")->get()->filter(fn ($row) => $row->creator_id)->keyBy('creator_id') : collect();
            $ids = $creations->keys()->merge($confirmations->keys())->unique();
            foreach ($ids as $id) $staff[] = ['name' => $creations->get($id)?->name ?? Admin::find($id)?->name ?? __('Former employee'), 'created' => (int) ($creations->get($id)?->total ?? 0), 'confirmed' => (int) ($confirmations->get($id)?->total ?? 0)];
            $staff = collect($staff)->sortByDesc(fn ($row) => $row['created'] + $row['confirmed'])->take(10)->values()->all();
        }
        return compact('definitions', 'providerCards', 'growth', 'revenue', 'health', 'queues', 'sources', 'staff', 'canCreators', 'canFinance', 'from', 'to') + [
            'chartLabels' => array_values($buckets), 'recent' => $recent->sortByDesc('date')->take(8)->values(),
            'totalRevenue' => collect($revenue)->sum(fn ($row) => array_sum($row['data'])),
            'activeCount' => collect($health)->sum('active'), 'expiringCount' => collect($health)->sum('expiring'),
            'registrationCount' => collect($providerCards)->sum('new'),
        ];
    }

    private function legacy(string $type, bool $includeDeleted = false)
    {
        $model = $type === 'doctor' ? DoctorSubscription::class : ClinicSubscription::class;
        $table = (new $model)->getTable();
        return $model::query()->whereHas($type, function ($query) use ($type, $includeDeleted) {
            if ($includeDeleted && $type === 'clinic') $query->withTrashed();
        })->whereNotExists(function ($query) use ($type, $table) {
            $query->selectRaw('1')->from('provider_payment_transactions as p')->join('provider_profiles as profile', 'profile.id', '=', 'p.provider_profile_id')
                ->where('profile.provider_type', $type)->whereColumn('profile.provider_id', $table.'.'.$type.'_id')
                ->whereColumn('p.gateway_reference', $table.'.transaction_code');
        });
    }
}
