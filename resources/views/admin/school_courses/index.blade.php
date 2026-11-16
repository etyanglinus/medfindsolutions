@extends('admin.master_layout')
@section('title')<title>{{ __('Course reviews') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><x-admin.breadcrumb title="{{ __('Course reviews') }}" /><div class="section-body">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>{{ __('Courses submitted by schools') }}</h4>
        <form method="GET">
            <select class="form-control" name="status" onchange="this.form.submit()">
                <option value="">{{ __('Waiting for review') }} ({{ ($counts['pending'] ?? 0) + ($counts['changes_required'] ?? 0) }})</option>
                @foreach(['approved' => __('Approved'), 'rejected' => __('Rejected'), 'changes_required' => __('Changes requested')] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }} ({{ $counts[$value] ?? 0 }})</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead><tr><th>{{ __('Course') }}</th><th>{{ __('School') }}</th><th>{{ __('Intake and fees') }}</th><th>{{ __('Status') }}</th><th style="min-width:280px">{{ __('Decision') }}</th></tr></thead>
            <tbody>
            @forelse($courses as $course)
                <tr>
                    <td><strong>{{ $course->name }}</strong>@if($course->is_featured)<span class="badge badge-warning ms-1">{{ __('Featured requested') }}</span>@endif
                        @if($course->description)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($course->description, 160) }}</div>@endif</td>
                    <td>{{ $course->institution?->name }}<div class="small text-muted">{{ $course->institution?->official_email }}</div></td>
                    <td class="small">@if($course->intake_starts_at){{ $course->intake_starts_at->format('d M Y') }}@if($course->intake_ends_at) – {{ $course->intake_ends_at->format('d M Y') }}@endif<br>@endif{{ $course->fees }}</td>
                    <td><span class="badge badge-{{ ['approved' => 'success', 'rejected' => 'danger', 'changes_required' => 'warning'][$course->approval_status] ?? 'info' }}">{{ __(ucfirst(str_replace('_', ' ', $course->approval_status))) }}</span>
                        @if($course->admin_note)<div class="small text-muted mt-1">{{ $course->admin_note }}</div>@endif</td>
                    <td>
                        @if(checkAdminHasPermission('school.update'))
                        <form method="POST" action="{{ route('admin.school-courses.review', $course) }}">@csrf @method('PUT')
                            <input name="admin_note" class="form-control form-control-sm mb-1" maxlength="1000" placeholder="{{ __('Note to the school (required unless approving)') }}">
                            <button name="approval_status" value="approved" class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                            <button name="approval_status" value="changes_required" class="btn btn-sm btn-warning">{{ __('Request changes') }}</button>
                            <button name="approval_status" value="rejected" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">{{ __('No courses in this list.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $courses->links() }}
    </div>
</div>
</div></section></div>
@endsection
