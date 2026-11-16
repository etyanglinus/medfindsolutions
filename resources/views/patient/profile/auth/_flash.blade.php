{{-- Shared flash message block for all auth pages --}}
@if(session('message'))
    @php
        $type = session('alert-type', 'info');
        $iconMap = ['success' => 'check-circle', 'warning' => 'exclamation-triangle', 'error' => 'times-circle', 'info' => 'info-circle'];
        $bsType  = $type === 'error' ? 'danger' : $type;
        $icon    = $iconMap[$type] ?? 'info-circle';
    @endphp
    <div class="alert alert-{{ $bsType }} alert-dismissible fade show rounded-3 d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="fas fa-{{ $icon }} mt-1 flex-shrink-0"></i>
        <span>{{ session('message') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
@endif
