<li class="menu-header">{{ __('Sales') }}</li>
<li><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i><span>{{ __('Sales dashboard') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.doctor.index') }}"><i class="fas fa-user-md"></i><span>{{ __('Specialists') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.clinic-listing.index') }}"><i class="fas fa-hospital"></i><span>{{ __('Facilities') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'school']) }}"><i class="fas fa-school"></i><span>{{ __('Schools') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'supplier']) }}"><i class="fas fa-boxes"></i><span>{{ __('Suppliers') }}</span></a></li>
@if(checkAdminHasPermission('client.payment.approve'))
@php $paybillWaitingCount = \App\Models\ProviderPaymentTransaction::where('gateway', 'mpesa_paybill')->where('status', 'pending')->count(); @endphp
<li><a class="nav-link" href="{{ route('admin.dashboard') }}#payments-to-verify"><i class="fas fa-mobile-alt"></i><span>{{ __('Payments to verify') }}</span>@if($paybillWaitingCount)<span class="badge badge-warning bg-warning text-dark ml-1 ms-1">{{ $paybillWaitingCount }}</span>@endif</a></li>
@endif
<li><a class="nav-link" href="{{ route('admin.edit-profile') }}"><i class="fas fa-user"></i><span>{{ __('My account') }}</span></a></li>
