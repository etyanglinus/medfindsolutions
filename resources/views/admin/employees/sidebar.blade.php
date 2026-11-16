<li class="menu-header">{{ __('Sales') }}</li>
<li><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i><span>{{ __('Sales dashboard') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.doctor.index') }}"><i class="fas fa-user-md"></i><span>{{ __('Specialists') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.clinic-listing.index') }}"><i class="fas fa-hospital"></i><span>{{ __('Facilities') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'school']) }}"><i class="fas fa-school"></i><span>{{ __('Schools') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.provider-profiles.index', ['type' => 'supplier']) }}"><i class="fas fa-boxes"></i><span>{{ __('Suppliers') }}</span></a></li>
<li><a class="nav-link" href="{{ route('admin.edit-profile') }}"><i class="fas fa-user"></i><span>{{ __('My account') }}</span></a></li>
