{{-- "Is this provider on the public site?" Expects $type (doctor|clinic|school|supplier) and $client. --}}
@php $listing = app(\App\Services\PublicDirectory::class)->status($type, $client); @endphp
<div class="mf-listing-status">
    @if($listing['listed'])
        <span class="badge bg-success badge-success"><i class="fas fa-globe-africa"></i> {{ __('Listed') }}</span>
        @if($listing['url'])<a class="small d-block mt-1" href="{{ $listing['url'] }}" target="_blank" rel="noopener">{{ __('View public page') }}</a>@endif
    @else
        <span class="badge bg-secondary badge-secondary"><i class="fas fa-eye-slash"></i> {{ __('Hidden') }}</span>
        <ul class="list-unstyled small text-muted mb-0 mt-1">@foreach($listing['reasons'] as $reason)<li>· {{ $reason }}</li>@endforeach</ul>
    @endif
    @if($listing['plan'])<div class="small mt-1">{{ $listing['plan'] }}@if($listing['expires']) · {{ $listing['expired'] ? __('ended') : __('until') }} {{ $listing['expires']->format('d M Y') }}@endif</div>@endif
    @if(in_array($type, ['doctor', 'clinic'], true))
        @adminCan($type === 'doctor' ? 'doctor.update' : 'facility.update')
            <form class="mt-1" method="POST" action="{{ route('admin.listing-completeness.update', [$type, $client->getKey()]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="allow_incomplete_listing" value="{{ $client->allow_incomplete_listing ? 0 : 1 }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    {{ $client->allow_incomplete_listing ? __('Require complete profile') : __('Show with incomplete profile') }}
                </button>
            </form>
        @endadminCan
    @endif
</div>
