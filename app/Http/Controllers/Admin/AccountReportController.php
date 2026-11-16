<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCreationAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountReportController extends Controller
{
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('account.report.view');
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['doctor', 'clinic', 'school', 'supplier'])],
            'source' => ['nullable', Rule::in(['admin', 'employee', 'self', 'system', 'unknown'])],
            'creator_id' => ['nullable', 'integer'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);
        $query = AccountCreationAudit::query()
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('provider_type', $value))
            ->when($filters['source'] ?? null, fn ($q, $value) => $q->where('source', $value))
            ->when($filters['creator_id'] ?? null, fn ($q, $value) => $q->where('creator_admin_id', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value));
        if (($filters['export'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Account type', 'Account ID', 'Name', 'Email', 'Source', 'Creator ID', 'Creator name', 'Creator email', 'Created at']);
                foreach ($query->orderBy('id')->cursor() as $row) {
                    $values = [$row->provider_type, $row->provider_id, $row->client_name, $row->client_email, $row->source, $row->creator_admin_id, $row->creator_name, $row->creator_email, $row->created_at?->toDateTimeString()];
                    fputcsv($output, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value, $values));
                }
                fclose($output);
            }, 'account-creation-report.csv', ['Content-Type' => 'text/csv']);
        }
        $summary = (clone $query)->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');
        $accounts = $query->latest('created_at')->latest('id')->paginate(30)->withQueryString();
        $creators = AccountCreationAudit::whereNotNull('creator_admin_id')->select('creator_admin_id', 'creator_name')->distinct()->get();
        return view('admin.reports.account-creation', compact('accounts', 'summary', 'creators'));
    }
}
