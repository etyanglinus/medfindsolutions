    <li class="nav-item dropdown {{ isRoute('admin.medicine*', 'active') }}">
        <a href="javascript:void()" class="nav-link has-dropdown"><i
                class="fas fa-newspaper"></i><span>{{ __('Medicine') }}</span></a>

        <ul class="dropdown-menu">
            @adminCan('medicine.view')
                <li class="{{ isRoute('admin.medicine.index', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.medicine.index', ['code' => getSessionLanguage()]) }}">
                        {{ __('Medicine') }}
                    </a>
                </li>
            @endadminCan
            @adminCan('medicine.type.view')
                <li class="{{ isRoute('admin.medicine.type.index', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.medicine.type.index', ['code' => getSessionLanguage()]) }}">
                        {{ __('Medicine Type') }}
                    </a>
                </li>
            @endadminCan
        </ul>
    </li>
