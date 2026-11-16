<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}"><img class="w-75" src="{{ asset($setting->logo) ?? '' }}"
                    alt="{{ $setting->app_name ?? '' }}"></a>
        </div>

        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('admin.dashboard') }}"><img src="{{ asset($setting->favicon) ?? '' }}"
                    alt="{{ $setting->app_name ?? '' }}"></a>
        </div>

        <ul class="sidebar-menu">
            @if(!auth('admin')->user()->is_super_admin)
                @include('admin.employees.sidebar')
            @else
            @adminCan('dashboard.view')
                <li class="{{ isRoute('admin.dashboard', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>
            @endadminCan

            @if (Module::isEnabled('Doctor'))
                @include('doctor::sidebar')
            @endif

            @if(checkAdminHasPermission('admin.view') || checkAdminHasPermission('role.view') || checkAdminHasPermission('account.report.view'))
            <li class="menu-header">{{ __('Administration') }}</li>
            @adminCan('admin.view')
            <li><a class="nav-link" href="{{ route('admin.admin.index') }}"><i class="fas fa-users"></i><span>{{ __('Employees') }}</span></a></li>
            @endadminCan
            @adminCan('role.view')
            <li><a class="nav-link" href="{{ route('admin.role.index') }}"><i class="fas fa-user-lock"></i><span>{{ __('Employee roles') }}</span></a></li>
            @endadminCan
            @adminCan('account.report.view')
            <li><a class="nav-link" href="{{ route('admin.reports.account-creation') }}"><i class="fas fa-chart-bar"></i><span>{{ __('Account creation report') }}</span></a></li>
            @endadminCan
            @endif
            @if (checkAdminHasPermission('doctor.view') ||
                    checkAdminHasPermission('department.view') ||
                    checkAdminHasPermission('location.view') ||
                    checkAdminHasPermission('service.view') ||
                    checkAdminHasPermission('blog.category.view') ||
                    checkAdminHasPermission('blog.view') || checkAdminHasPermission('blog.comment.view'))
                <li class="menu-header">{{ __('Manage Contents') }}</li>
                @if (Module::isEnabled('Blog') && (checkAdminHasPermission('blog.category.view') || checkAdminHasPermission('blog.view') || checkAdminHasPermission('blog.comment.view')))
                    @include('blog::sidebar')
                @endif

            @endif

            @if (checkAdminHasPermission('menu.view') ||
                    checkAdminHasPermission('slider.view') ||
                    checkAdminHasPermission('feature.view') ||
                    checkAdminHasPermission('work.section.view') ||
                    checkAdminHasPermission('counter.view') ||
                    checkAdminHasPermission('partner.view') ||
                    checkAdminHasPermission('page.aboutus.view') ||
                    checkAdminHasPermission('page.faq.view') ||
                    checkAdminHasPermission('faq.view') ||
                    checkAdminHasPermission('social.link.management') || checkAdminHasPermission('faq.category.view') ||
                    checkAdminHasPermission('page.view') ||
                    checkAdminHasPermission('section.view'))

                <li class="menu-header">{{ __('Manage Website') }}</li>

                @if (Module::isEnabled('CustomMenu') && checkAdminHasPermission('menu.view'))
                    @include('custommenu::sidebar')
                @endif

                @if (Module::isEnabled('HomeSection') &&
                        (checkAdminHasPermission('slider.view') ||
                            checkAdminHasPermission('feature.view') ||
                            checkAdminHasPermission('work.section.view') ||
                            checkAdminHasPermission('counter.view') ||
                            checkAdminHasPermission('partner.view') ||
                            checkAdminHasPermission('section.view')))
                    @include('homesection::sidebar')
                @endif

                @if (checkAdminHasPermission('page.aboutus.view') || checkAdminHasPermission('page.faq.view') || checkAdminHasPermission('page.view'))
                    <li
                        class="nav-item dropdown {{ isRoute(['admin.pages.about-us.index', 'admin.pages.contact-page.index', 'admin.pages.faq-page.index', 'admin.custom-pages.*', 'admin.pages.utility-page.index', 'admin.social.media.*','admin.pages.faq.index'], 'active') }}">
                        <a href="javascript:;" class="nav-link has-dropdown">
                            <i class="fas fa-th"></i><span>{{ __('Pages') }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            @if (checkAdminHasPermission('page.aboutus.view'))
                                <li class="{{ isRoute('admin.pages.about-us.index', 'active') }}">
                                    <a class="nav-link"
                                        href="{{ route('admin.pages.about-us.index', ['code' => getSessionLanguage()]) }}">
                                        {{ __('About Us') }}
                                    </a>
                                </li>
                            @endif
                            @if (checkAdminHasPermission('page.faq.view'))
                                <li class="{{ isRoute('admin.pages.faq.index', 'active') }}">
                                    <a class="nav-link"
                                        href="{{ route('admin.pages.faq.index') }}">
                                        {{ __('FAQ Page') }}
                                    </a>
                                </li>
                            @endif
                            @if (Module::isEnabled('PageBuilder') && checkAdminHasPermission('page.view'))
                                @include('pagebuilder::sidebar')
                            @endif
                        </ul>
                    </li>
                @endif

                @if (Module::isEnabled('Faq') && checkAdminHasPermission('faq.category.view'))
                    @include('faq::sidebar')
                @endif

                @if (Module::isEnabled('SocialLink') && checkAdminHasPermission('social.link.management'))
                    @include('sociallink::sidebar')
                @endif

            @endif
            @if (checkAdminHasPermission('setting.view') ||
                    checkAdminHasPermission('basic.payment.view') ||
                    checkAdminHasPermission('currency.view') ||
                    checkAdminHasPermission('language.view') ||
                    checkAdminHasPermission('role.view') || 
                    checkAdminHasPermission('addon.view') ||
                    checkAdminHasPermission('admin.view'))

                <li class="menu-header">{{ __('Others') }}</li>

                @if (Module::isEnabled('GlobalSetting'))
                    <li class="{{ isRoute('admin.settings', 'active') }}">
                        <a class="nav-link" href="{{ route('admin.settings') }}"><i class="fas fa-cog"></i>
                            <span>{{ __('Settings') }}</span>
                        </a>
                    </li>
                    <li class="{{ isRoute('admin.test-mail', 'active') }}">
                        <a class="nav-link" href="{{ route('admin.test-mail') }}">
                            <i class="fas fa-paper-plane"></i>
                            <span>{{ __('Test Mail') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            @if (Module::isEnabled('App') && checkAdminHasPermission('app.management'))
                @include('app::sidebar')
            @endif

            @if (checkAdminHasPermission('doctor.view'))
                <li class="{{ isRoute('admin.provider-messages.*', 'active') }}">
                    <a class="nav-link" href="{{ route('admin.provider-messages.index') }}"><i class="fas fa-comments"></i><span>{{ __('Provider Messages') }}</span></a>
                </li>
            @endif

            @if (Module::isEnabled('NewsLetter') && (checkAdminHasPermission('newsletter.view') || checkAdminHasPermission('newsletter.mail') || checkAdminHasPermission('newsletter.content.view')))
                @include('newsletter::sidebar')
            @endif

            @if (Module::isEnabled('ContactMessage') && (checkAdminHasPermission('contact.message.view') || checkAdminHasPermission('contact.info.view')))
                @include('contactmessage::sidebar')
            @endif
            <li class="nav-item dropdown {{ isRoute('admin.addon.*') ? 'active' : '' }}" id="addon_sidemenu">
                <a class="nav-link has-dropdown" data-toggle="dropdown" href="#"><i class="fas fa-gem"></i>
                    <span>{{ __('Manage Addons') }} </span>
                </a>
                <ul class="dropdown-menu addon_menu">
                    @includeIf('admin.addons')
                </ul>
            </li>
            @endif
        </ul>
    </aside>
</div>
