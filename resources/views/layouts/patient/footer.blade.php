<!--Footer Start-->
<footer class="main-footer mf-site-footer">

    <div class="footer-area mf-site-footer__area">
        <div class="container">

            <div class="row g-4 justify-content-between">

                {{-- ── Column 1 · About ── --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="footer-item">
                        <h5 class="text-white fw-bold mb-3" style="font-size:15px;letter-spacing:.3px">About MedFind</h5>
                        <p style="color:#cbd5e1;line-height:1.75;font-size:.85rem">
                            MedFind Solutions connects patients with licensed specialists, clinics, diagnostic centres,
                            pharmacies, healthcare schools and medical suppliers across Kenya — all from one trusted platform.
                        </p>
                        {{-- Static social icons ── update hrefs as needed --}}
                        <ul class="list-unstyled d-flex align-items-center gap-2 mt-3 mb-0 p-0">
                            <li>
                                <a href="https://facebook.com/medfindsolutions" target="_blank" rel="noopener"
                                   aria-label="Facebook"
                                   class="footer-hover d-flex align-items-center justify-content-center rounded-circle"
                                   style="width:36px;height:36px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:.9rem">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            </li>
                            <li>
                                <a href="https://twitter.com/medfindsolutions" target="_blank" rel="noopener"
                                   aria-label="X / Twitter"
                                   class="footer-hover d-flex align-items-center justify-content-center rounded-circle"
                                   style="width:36px;height:36px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:.9rem">
                                    <i class="fab fa-x-twitter"></i>
                                </a>
                            </li>
                            <li>
                                <a href="https://instagram.com/medfindsolutions" target="_blank" rel="noopener"
                                   aria-label="Instagram"
                                   class="footer-hover d-flex align-items-center justify-content-center rounded-circle"
                                   style="width:36px;height:36px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:.9rem">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </li>
                            <li>
                                <a href="https://linkedin.com/company/medfindsolutions" target="_blank" rel="noopener"
                                   aria-label="LinkedIn"
                                   class="footer-hover d-flex align-items-center justify-content-center rounded-circle"
                                   style="width:36px;height:36px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:.9rem">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            </li>
                            <li>
                                <a href="https://wa.me/254700000000" target="_blank" rel="noopener"
                                   aria-label="WhatsApp"
                                   class="footer-hover d-flex align-items-center justify-content-center rounded-circle"
                                   style="width:36px;height:36px;background:#1e293b;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:.9rem">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- ── Column 2 · Explore ── --}}
                <div class="col-xl-2 col-lg-2 col-sm-6">
                    <div class="footer-item">
                        <h5 class="text-white fw-bold mb-3" style="font-size:15px;letter-spacing:.3px">Explore</h5>
                        <ul class="list-unstyled d-flex flex-column gap-2 m-0 p-0">
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('doctors') }}">Find a Specialist</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('facilities') }}">Medical Facilities</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('website.healthcare-courses') }}">Healthcare Schools</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('website.medical-suppliers') }}">Medical Suppliers</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('blog') }}">Health Blog</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('department') }}">Departments</a></li>
                        </ul>
                    </div>
                </div>

                {{-- ── Column 3 · Legal ── --}}
                <div class="col-xl-2 col-lg-2 col-sm-6">
                    <div class="footer-item">
                        <h5 class="text-white fw-bold mb-3" style="font-size:15px;letter-spacing:.3px">Legal</h5>
                        <ul class="list-unstyled d-flex flex-column gap-2 m-0 p-0">
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('privacy-policy') }}">Privacy Policy</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('terms-condition') }}">Terms &amp; Conditions</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('faq') }}">FAQ</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ url('about-us') }}">About Us</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="mailto:info@medfindsolutions.co.ke">Contact Us</a></li>
                        </ul>
                    </div>
                </div>

                {{-- ── Column 4 · Join ── --}}
                <div class="col-xl-2 col-lg-2 col-sm-6">
                    <div class="footer-item">
                        <h5 class="text-white fw-bold mb-3" style="font-size:15px;letter-spacing:.3px">Join MedFind</h5>
                        <ul class="list-unstyled d-flex flex-column gap-2 m-0 p-0">
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('provider.register', ['type' => 'specialist']) }}">Register as Specialist</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('provider.register', ['type' => 'clinic']) }}">Register as Facility</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('website.partner.register', ['type' => 'supplier']) }}">Register as Supplier</a></li>
                            <li><a class="footer-hover d-block py-1 text-decoration-none" style="color:#94a3b8" href="{{ route('website.partner.register', ['type' => 'school']) }}">Register as School</a></li>
                            <li style="margin-top:.5rem">
                                <a class="d-inline-flex align-items-center gap-2 text-decoration-none rounded-pill px-3 py-2"
                                   href="{{ route('provider.register', ['type' => 'specialist']) }}"
                                   style="background:#0284c7;color:#fff;font-size:.78rem;font-weight:600;line-height:1">
                                    <i class="fas fa-plus-circle"></i> Get listed today
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- ── Column 5 · Contact ── --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="footer-item">
                        <h5 class="text-white fw-bold mb-3" style="font-size:15px;letter-spacing:.3px">Get in Touch</h5>
                        <ul class="list-unstyled d-flex flex-column gap-3 m-0 p-0">
                            <li class="d-flex align-items-start gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"
                                     style="width:34px;height:34px;background:#1e293b;border:1px solid #334155">
                                    <i class="far fa-envelope" style="color:#38bdf8;font-size:.9rem"></i>
                                </div>
                                <div>
                                    <small class="text-uppercase d-block fw-semibold mb-1" style="font-size:11px;color:#64748b;letter-spacing:.5px">Email</small>
                                    <a href="mailto:info@medfindsolutions.co.ke"
                                       class="text-white text-decoration-none footer-hover fw-medium" style="font-size:.85rem">
                                        info@medfindsolutions.co.ke
                                    </a>
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"
                                     style="width:34px;height:34px;background:#1e293b;border:1px solid #334155">
                                    <i class="fas fa-phone-alt" style="color:#38bdf8;font-size:.9rem"></i>
                                </div>
                                <div>
                                    <small class="text-uppercase d-block fw-semibold mb-1" style="font-size:11px;color:#64748b;letter-spacing:.5px">Phone / WhatsApp</small>
                                    <a href="tel:+254700000000"
                                       class="text-white text-decoration-none footer-hover fw-medium" style="font-size:.85rem">
                                        +254 117 295 891
                                    </a>
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"
                                     style="width:34px;height:34px;background:#1e293b;border:1px solid #334155">
                                    <i class="fas fa-map-marker-alt" style="color:#38bdf8;font-size:.9rem"></i>
                                </div>
                                <div>
                                    <small class="text-uppercase d-block fw-semibold mb-1" style="font-size:11px;color:#64748b;letter-spacing:.5px">Location</small>
                                    <span class="text-white fw-medium" style="font-size:.85rem;line-height:1.6">
                                        Nairobi, Kenya
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>{{-- /row --}}
        </div>{{-- /container --}}
    </div>{{-- /footer-area --}}

    {{-- ── Copyright bar ── --}}
    

</footer>
<!--Footer End-->

<!-- Floating UI Navigation Element -->
<div class="scroll-top position-fixed bottom-0 end-0 m-4 rounded-circle bg-info text-white d-flex align-items-center justify-content-center shadow" style="width: 42px; height: 42px; cursor: pointer; z-index: 1050; transition: all 0.2s ease-in-out;">
    <i class="fas fa-angle-double-up"></i>
</div>

<!-- Clean CSS Rules Supporting Microinteractions -->
<style>
    .main-footer .footer-hover { transition: all 0.2s ease-in-out; }
    .main-footer a.footer-hover:hover { color: #0dcaf0 !important; transform: translateX(3px); }
    .main-footer ul li a.footer-hover:hover { transform: translateY(-2px); background-color: #334155 !important; border-color: #0dcaf0 !important; }
</style>

<!-- Contextual Configuration Flags Setup -->
<script>
    var isRtl = "{{ session()->get('text_direction') == 'rtl' }}";
    var rtlTrue = false;
    if (isRtl) {
        rtlTrue = true;
    }
</script>

<!-- Global Script Dependencies Framework Bundle Layout Core -->
<script src="{{ asset('global/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('backend/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('patient/js/popper.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.collapse.js') }}"></script>
<script src="{{ asset('patient/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('patient/js/swiper-bundle.js') }}"></script>
<script src="{{ asset('patient/js/jquery.filterizr.min.js') }}"></script>
<script src="{{ asset('patient/js/select2.min.js') }}"></script>
<script src="{{ asset('patient/js/wow.min.js') }}"></script>
<script src="{{ asset('patient/js/slick.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('patient/js/viewportchecker.js') }}"></script>
<script src="{{ asset('patient/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.waypoints.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('patient/js/custom.js') }}?v={{ $setting?->version }}"></script>
<script src="{{ asset('global/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('patient/js/jquery-ui.js') }}"></script>
<script src="{{ asset('https://js.pusher.com/7.0/pusher.min.js') }}"></script>
@include('patient.dynamic-js-variables')
<script src="{{ asset('patient/js/ajax-request.js') }}"></script>

<!-- Notifications Display Handler Engine Loops -->
<script>
    @if (Session::has('message'))
        var type = "{{ Session::get('alert-type', 'info') }}";
        switch (type) {
            case 'info':
                toastr.info("{{ Session::get('message') }}");
                break;
            case 'success':
                toastr.success("{{ Session::get('message') }}");
                break;
            case 'warning':
                toastr.warning("{{ Session::get('message') }}");
                break;
            case 'error':
                toastr.error("{{ Session::get('message') }}");
                break;
        }
    @endif
</script>

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script>
            toastr.error('{{ $error }}');
        </script>
    @endforeach
@endif

<!-- Instant Direct Messaging Component Plugin -->
@if ($setting->tawk_status == 'active')
    <script type="text/javascript">
        "use strict";
        var Tawk_API = Tawk_API || {},
            Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = '{{ $setting->tawk_chat_link }}';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
@endif

<!-- Cookie consent: a thin bar with Accept / Decline. The choice is kept for a year in mf_cookie_consent. -->
@if ($setting->cookie_status == 'active')
    <div id="mf-cookie-bar" class="mf-cookie-bar" role="region" aria-label="{{ __('Cookie consent') }}" hidden>
        <p class="mf-cookie-bar__text">{{ __('We use essential cookies to run this site, and analytics cookies only if you accept.') }} <a href="{{ route('website.privacy-policy') }}">{{ $setting->link_text ?: __('Privacy policy') }}</a></p>
        <div class="mf-cookie-bar__actions">
            <button type="button" class="mf-cookie-bar__btn mf-cookie-bar__btn--ghost" data-consent="declined">{{ __('Decline') }}</button>
            <button type="button" class="mf-cookie-bar__btn" data-consent="accepted">{{ __('Accept') }}</button>
        </div>
    </div>
    <style>
        .mf-cookie-bar{position:fixed;left:12px;right:12px;bottom:12px;z-index:1080;display:flex;align-items:center;gap:12px;max-width:760px;margin:0 auto;padding:10px 12px 10px 16px;background:#0f172a;color:#e2e8f0;border-radius:12px;box-shadow:0 8px 24px rgba(15,23,42,.25);font-size:13px;line-height:1.4}
        .mf-cookie-bar[hidden]{display:none}
        .mf-cookie-bar__text{margin:0;flex:1;color:inherit}
        .mf-cookie-bar__text a{color:#93c5fd;text-decoration:underline}
        .mf-cookie-bar__actions{display:flex;gap:8px;flex-shrink:0}
        .mf-cookie-bar__btn{border:0;border-radius:999px;padding:8px 16px;font-size:13px;font-weight:600;background:#2b5fde;color:#fff;cursor:pointer;min-height:36px}
        .mf-cookie-bar__btn--ghost{background:transparent;color:#e2e8f0;border:1px solid #475569}
        .mf-cookie-bar__btn:focus-visible{outline:2px solid #93c5fd;outline-offset:2px}
        @media (max-width:575.98px){.mf-cookie-bar{left:8px;right:8px;bottom:8px;flex-wrap:wrap;gap:8px;padding:10px 12px}.mf-cookie-bar__actions{width:100%}.mf-cookie-bar__btn{flex:1}}
    </style>
    <script>
        "use strict";
        (function () {
            var bar = document.getElementById('mf-cookie-bar');
            if (!bar || /(?:^|;\s*)mf_cookie_consent=/.test(document.cookie)) return;
            bar.hidden = false;
            bar.addEventListener('click', function (e) {
                var choice = e.target.closest('[data-consent]');
                if (!choice) return;
                var value = choice.getAttribute('data-consent');
                document.cookie = 'mf_cookie_consent=' + value + ';max-age=31536000;path=/;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
                bar.hidden = true;
                if (value === 'accepted' && window.mfLoadTrackers) window.mfLoadTrackers();
            });
        })();
    </script>
@endif

@if (\App\Services\Tafuta\TafutaAssistant::enabled())
    @include('tafuta.widget')
@endif

@stack('js')

@if (customCode()?->footer_javascript)
    <script>
        "use strict";
        {!! customCode()->footer_javascript !!}
    </script>
@endif

</body>
</html>
