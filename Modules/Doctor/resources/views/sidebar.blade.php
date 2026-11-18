<li class="menu-header">{{ __('Providers') }}</li>
@adminCan('doctor.view')
<li><a class="nav-link" href="{{ route('admin.doctor.index') }}"><i class="fas fa-user-md"></i><span>{{ __('Doctors / Specialists') }}</span></a></li>
@endadminCan
@adminCan('facility.view')
<li><a class="nav-link" href="{{ route('admin.clinic-listing.index') }}"><i class="fas fa-hospital"></i><span>{{ __('Facilities') }}</span></a></li>
@endadminCan
@adminCan('school.view')
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'school']) }}"><i class="fas fa-school"></i><span>{{ __('Schools') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.school-courses.index') }}"><i class="fas fa-book-open"></i><span>{{ __('Course reviews') }}</span></a></li>
@endadminCan
@adminCan('supplier.view')
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'supplier']) }}"><i class="fas fa-boxes"></i><span>{{ __('Suppliers') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.supplier-listings.index') }}"><i class="fas fa-box-open"></i><span>{{ __('Product reviews') }}</span></a></li>
@endadminCan
@adminCan('doctor.view')
<li><a class="nav-link" href="{{ route('admin.provider-documents.index', ['provider_type' => 'school']) }}"><i class="fas fa-file-alt"></i><span>{{ __('Provider Documents') }}</span></a></li>
@endadminCan
<li class="menu-header">{{ __('Subscriptions') }}</li>
@adminCan('doctor.view')
<li><a class="nav-link" href="{{ route('admin.subscriptions.doctor.index') }}"><i class="fas fa-credit-card"></i><span>{{ __('Specialist & Facility Payments') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.subscriptions.packages.index') }}"><i class="fas fa-layer-group"></i><span>{{ __('Subscription Plans') }}</span></a></li>
@endadminCan
@adminCan('client.payment.view')
@php $paybillWaitingCount = \App\Models\ProviderPaymentTransaction::where('gateway', 'mpesa_paybill')->where('status', 'pending')->count(); @endphp
<li><a class="nav-link" href="{{ route('admin.provider-payments.index') }}"><i class="fas fa-money-check-alt"></i><span>{{ __('Client Payment Reviews') }}</span>@if($paybillWaitingCount)<span class="badge badge-warning bg-warning text-dark ml-1 ms-1">{{ $paybillWaitingCount }}</span>@endif</a></li>
@endadminCan
@adminCan('setting.view')
<li><a class="nav-link" href="{{ route('admin.subscriptions.payment-details') }}"><i class="fas fa-mobile-alt"></i><span>{{ __('M-Pesa Paybill') }}</span></a></li>
@endadminCan
@adminCan('setting.view')
<li><a class="nav-link" href="{{ route('admin.subscription-coupons.index') }}"><i class="fas fa-ticket-alt"></i><span>{{ __('Subscription Coupons') }}</span></a></li>
@endadminCan
<li class="menu-header">{{ __('Provider Settings') }}</li>
@adminCan('department.view')
<li><a class="nav-link" href="{{ route('admin.department.index') }}"><i class="fas fa-stethoscope"></i><span>{{ __('Specialties') }}</span></a></li>
@endadminCan
@adminCan('service.view')
<li><a class="nav-link" href="{{ route('admin.service.index') }}"><i class="fas fa-briefcase-medical"></i><span>{{ __('Services') }}</span></a></li>
@endadminCan
@adminCan('licensingbody.view')
<li><a class="nav-link" href="{{ route('admin.licensingbody.index') }}"><i class="fas fa-certificate"></i><span>{{ __('Licensing Bodies') }}</span></a></li>
@endadminCan
@adminCan('insuranceprovider.view')
<li><a class="nav-link" href="{{ route('admin.insuranceprovider.index') }}"><i class="fas fa-shield-alt"></i><span>{{ __('Insurance Providers') }}</span></a></li>
@endadminCan