@extends('admin.master_layout')

@section('title')
    <title>{{ __('Test Mail') }} — {{ config('app.name') }}</title>
@endsection

@push('css')
<style>
.mail-config-table td:first-child { width: 160px; font-weight: 600; color: #6c757d; font-size: .82rem; text-transform: uppercase; letter-spacing: .04em; }
.mail-config-table td              { padding: 8px 12px; font-size: .9rem; vertical-align: middle; }
.mail-config-table tr:last-child td { border-bottom: none; }

.result-card            { border-radius: 14px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,.10); overflow: hidden; }
.result-card .icon-wrap { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 12px; }
.result-card.success .icon-wrap { background: #d1fae5; color: #059669; }
.result-card.error   .icon-wrap { background: #fee2e2; color: #dc2626; }

.smtp-row { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 10px; background: #f8f9fa; border: 1px solid #e9ecef; }
.smtp-row .smtp-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 600; min-width: 90px; }
.smtp-row .smtp-val   { font-size: .9rem; font-weight: 500; color: #212529; }
</style>
@endpush

@section('admin-content')
<div class="main-content">
  <section class="section">
    <x-admin.breadcrumb title="{{ __('Test Mail') }}" :list="[
        __('Dashboard') => route('admin.dashboard'),
        __('Settings')  => route('admin.settings'),
        __('Test Mail') => '#',
    ]" />

    <div class="section-body">
      <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">

          {{-- ── Result banner ──────────────────────────────── --}}
          @if(session('test_mail_success'))
            <div class="card result-card success mb-4">
              <div class="card-body text-center py-5">
                <div class="icon-wrap"><i class="fas fa-check"></i></div>
                <h5 class="fw-bold text-success mb-2">{{ __('Email Delivered!') }}</h5>
                <p class="text-muted mb-0">{{ session('test_mail_success') }}</p>
              </div>
            </div>
          @elseif(session('test_mail_error'))
            <div class="card result-card error mb-4">
              <div class="card-body py-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="icon-wrap flex-shrink-0" style="width:44px;height:44px;font-size:1.1rem;margin:0;">
                    <i class="fas fa-times"></i>
                  </div>
                  <div>
                    <h6 class="fw-bold text-danger mb-1">{{ __('Delivery Failed') }}</h6>
                    <p class="text-muted small mb-2">{{ __('The test email could not be sent. Error from the mail server:') }}</p>
                    <code class="d-block bg-light border rounded p-3" style="font-size:.8rem;word-break:break-all;white-space:pre-wrap;">{{ session('test_mail_error') }}</code>
                    <p class="small text-muted mt-3 mb-0">
                      {{ __('Common fixes:') }}
                      <ul class="mt-1 mb-0 ps-4 small text-muted">
                        <li>{{ __('Verify MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD in .env') }}</li>
                        <li>{{ __('Ensure port 465 uses MAIL_ENCRYPTION=ssl and port 587 uses tls') }}</li>
                        <li>{{ __('Check that your hosting server allows outbound SMTP on this port') }}</li>
                        <li>{{ __('Clear config cache: php artisan config:clear') }}</li>
                      </ul>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          @endif

          {{-- ── Active SMTP config summary ─────────────────── --}}
          <div class="card mb-4" style="border-radius:12px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
              <h6 class="fw-bold mb-0">
                <i class="fas fa-server text-primary me-2"></i>{{ __('Current SMTP Configuration') }}
              </h6>
              <p class="text-muted small mb-0">{{ __('Read from config/mail.php and .env — cached values shown.') }}</p>
            </div>
            <div class="card-body p-4 pt-3">
              <div class="d-flex flex-column gap-2">
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('Mailer') }}</span>
                  <span class="smtp-val">{{ strtoupper($config['mailer']) }}</span>
                </div>
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('Host') }}</span>
                  <span class="smtp-val">{{ $config['host'] ?: __('(not set)') }}</span>
                </div>
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('Port') }}</span>
                  <span class="smtp-val">{{ $config['port'] ?: __('(not set)') }}</span>
                  @php
                    $port = (int) $config['port'];
                    $enc  = strtolower($config['encryption'] ?? '');
                    $portOk = ($port === 465 && $enc === 'ssl') || ($port === 587 && in_array($enc, ['tls','starttls'])) || ($port === 25);
                  @endphp
                  @if(! $portOk)
                    <span class="badge bg-warning text-dark ms-2" title="{{ __('Port/encryption combination may not be standard') }}">
                      <i class="fas fa-exclamation-triangle me-1"></i>{{ __('Check pairing') }}
                    </span>
                  @endif
                </div>
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('Encryption') }}</span>
                  <span class="smtp-val">{{ strtoupper($config['encryption'] ?: __('none')) }}</span>
                </div>
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('Username') }}</span>
                  <span class="smtp-val">{{ $config['username'] ?: __('(not set)') }}</span>
                </div>
                <div class="smtp-row">
                  <span class="smtp-label">{{ __('From') }}</span>
                  <span class="smtp-val">{{ $config['from'] ?: __('(not set)') }}</span>
                </div>
              </div>

              @if(! $config['host'] || ! $config['username'])
                <div class="alert alert-warning d-flex gap-2 align-items-center mt-3 mb-0 small">
                  <i class="fas fa-exclamation-triangle"></i>
                  <span>{{ __('Some required SMTP values are missing. Update MAIL_HOST and MAIL_USERNAME in your .env file then run') }}
                    <code>php artisan config:clear</code>.
                  </span>
                </div>
              @endif
            </div>
          </div>

          {{-- ── Send test card ──────────────────────────────── --}}
          <div class="card" style="border-radius:12px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
              <h6 class="fw-bold mb-0">
                <i class="fas fa-paper-plane text-success me-2"></i>{{ __('Send a Test Email') }}
              </h6>
              <p class="text-muted small mb-0">{{ __('Enter any email address. If the message arrives, your SMTP is configured correctly.') }}</p>
            </div>
            <div class="card-body p-4">
              <form method="POST" action="{{ route('admin.test-mail.send') }}" id="testMailForm">
                @csrf
                <div class="mb-3">
                  <label for="to" class="form-label fw-semibold small">
                    {{ __('Recipient Email') }} <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="to" id="to"
                      class="form-control @error('to') is-invalid @enderror"
                      placeholder="you@example.com"
                      value="{{ old('to', auth('admin')->user()?->email) }}"
                      required>
                    @error('to')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="form-text text-muted">{{ __('The test message will arrive from') }} <strong>{{ $config['from'] ?: __('(from address not set)') }}</strong>.</div>
                </div>

                <button type="submit" class="btn btn-primary px-5 fw-semibold" id="sendBtn">
                  <i class="fas fa-paper-plane me-2"></i>{{ __('Send Test Email') }}
                </button>

                <div id="sendSpinner" class="d-none mt-3 text-muted small">
                  <div class="spinner-border spinner-border-sm me-2"></div>
                  {{ __('Connecting to mail server — this may take up to 30 seconds…') }}
                </div>
              </form>
            </div>
          </div>

          {{-- Back link --}}
          <div class="mt-4">
            <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary rounded-pill px-4">
              <i class="fas fa-arrow-left me-2"></i>{{ __('Back to Settings') }}
            </a>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@push('js')
<script>
document.getElementById('testMailForm').addEventListener('submit', function () {
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('sendSpinner').classList.remove('d-none');
});
</script>
@endpush
