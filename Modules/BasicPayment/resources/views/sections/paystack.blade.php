<div class="tab-pane fade show active" id="paystack_tab" role="tabpanel">
    @php
        $paystackCallbackUrl = \App\Services\ProviderPaystackService::callbackUrl();
        $paystackWebhookUrl = \App\Services\ProviderPaystackService::webhookUrl();
        $paystackUrlsLookLocal = !str_starts_with($paystackWebhookUrl, 'https://') || preg_match('/localhost|127\.0\.0\.1/', $paystackWebhookUrl);
    @endphp
    <div class="alert alert-info">
        <h6 class="mb-2"><i class="fas fa-link"></i> {{ __('Paystack dashboard setup (Settings → API Keys & Webhooks)') }}</h6>
        <p class="mb-2 small">{{ __('Copy these two URLs into your Paystack dashboard. The callback brings the doctor, facility, supplier or school back here after paying; the webhook confirms the payment even if they close the browser before returning.') }}</p>
        <div class="form-group mb-2">
            <label class="small mb-1 font-weight-bold">{{ __('Callback URL') }}</label>
            <div class="input-group">
                <input type="text" class="form-control" id="paystack_callback_url" value="{{ $paystackCallbackUrl }}" readonly>
                <div class="input-group-append"><button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('paystack_callback_url').value);this.innerText='{{ __('Copied') }}'">{{ __('Copy') }}</button></div>
            </div>
        </div>
        <div class="form-group mb-2">
            <label class="small mb-1 font-weight-bold">{{ __('Webhook URL') }}</label>
            <div class="input-group">
                <input type="text" class="form-control" id="paystack_webhook_url" value="{{ $paystackWebhookUrl }}" readonly>
                <div class="input-group-append"><button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('paystack_webhook_url').value);this.innerText='{{ __('Copied') }}'">{{ __('Copy') }}</button></div>
            </div>
        </div>
        <p class="mb-0 small">{{ __('Use the Live URLs with your live keys and the Test URLs with your test keys. Paystack signs each webhook with your secret key, so the secret key saved below must match the mode you are using.') }}</p>
    </div>
    @if($paystackUrlsLookLocal)
        <div class="alert alert-danger">{{ __('These URLs are not public HTTPS addresses, so Paystack cannot reach them. Open this page from your live domain (https://...) and set APP_URL in .env to that domain.') }}</div>
    @endif

    <form action="{{ route('admin.paystack-update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="form-group col-md-6">
                <x-admin.form-input id="paystack_charge" name="paystack_charge" label="{{ __('Gateway charge') }}(%)"
                    value="{{ $basic_payment->paystack_charge }}" />
            </div>
            <div class="form-group col-md-6">
                @if (env('APP_MODE') == 'DEMO')
                    <x-admin.form-input id="paystack_public_key" name="paystack_public_key" label="{{ __('Public key') }}"
                        value="paystack-test-348949439-public-key" required="true" />
                @else
                    <x-admin.form-input id="paystack_public_key" name="paystack_public_key"
                        label="{{ __('Public key') }}" value="{{ $basic_payment->paystack_public_key }}"
                        required="true" />
                @endif
            </div>

            <div class="form-group col-md-12">
                @if (env('APP_MODE') == 'DEMO')
                    <x-admin.form-input id="paystack_secret_key" name="paystack_secret_key"
                        label="{{ __('Secret key') }}" value="paystack-test-8384934-key-secret" required="true" />
                @else
                    <x-admin.form-input id="paystack_secret_key" name="paystack_secret_key"
                        label="{{ __('Secret key') }}" value="{{ $basic_payment->paystack_secret_key }}"
                        required="true" />
                @endif
            </div>

        </div>

        <div class="form-group">
            <x-admin.form-image-preview recommended="200X110" div_id="paystack_image_preview" label_id="paystack_image_label"
                input_id="paystack_image_upload" :image="$basic_payment->paystack_image" name="paystack_image"
                label="{{ __('Existing Image') }}" button_label="{{ __('Update Image') }}" required="0" />
        </div>
        <div class="form-group">
            <x-admin.form-switch name="paystack_status" label="{{ __('Status') }}" active_value="active"
                inactive_value="inactive" :checked="$basic_payment->paystack_status == 'active'" />
        </div>

        <x-admin.update-button :text="__('Update')" />
    </form>
</div>
