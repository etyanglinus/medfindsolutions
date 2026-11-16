<div class="tab-pane fade" id="mmaintenance_mode_tab" role="tabpanel">
    <div class="form-group">
        <div class="alert alert-warning alert-has-icon">
            <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
            <div class="alert-body">
                <div class="alert-title">{{ __('Warning') }}</div>
                {{ __("If you enable maintenance mode, regular users won't be able to access the website. Please make sure to inform users about the temporary unavailability.") }}
            </div>
        </div>
        @php($maintenanceService = app(\App\Services\MaintenanceService::class))
        @php($maintenanceOn = $maintenanceService->isActive())
        @php($maintenanceUntil = $maintenanceService->until())
        <div class="card border mb-3"><div class="card-body">
            @if($maintenanceOn)
                <p class="mb-2"><span class="badge badge-danger">{{ __('Maintenance is ON') }}</span>
                    @if($maintenanceUntil) {{ __('Ends automatically at :time (:left).', ['time' => $maintenanceUntil->format('j M Y, g:i A'), 'left' => $maintenanceUntil->diffForHumans()]) }}
                    @else {{ __('Until you switch it off.') }} @endif</p>
                <p class="small text-muted">{{ __('You are signed in as an admin, so you can still browse the site to check your work. Paystack payments keep working.') }}</p>
                <form method="POST" action="{{ route('admin.maintenance.stop') }}">@csrf<button class="btn btn-success">{{ __('Turn maintenance off now') }}</button></form>
            @else
                <p class="mb-2"><span class="badge badge-success">{{ __('Site is open') }}</span></p>
                <form method="POST" action="{{ route('admin.maintenance.start') }}" class="row g-2 align-items-end">@csrf
                    <div class="col-md-5"><label class="form-label">{{ __('Close the site for') }}</label>
                        <select name="duration" class="form-control" onchange="document.getElementById('maintenance-until-wrap').style.display = this.value === 'until' ? 'block' : 'none'">
                            @foreach([15 => __('15 minutes'), 30 => __('30 minutes'), 60 => __('1 hour'), 120 => __('2 hours'), 240 => __('4 hours'), 720 => __('12 hours'), 1440 => __('24 hours')] as $minutes => $label)<option value="{{ $minutes }}" @selected($minutes === 60)>{{ $label }}</option>@endforeach
                            <option value="until">{{ __('Until a date and time…') }}</option>
                            <option value="manual">{{ __('Until I switch it off') }}</option>
                        </select></div>
                    <div class="col-md-4" id="maintenance-until-wrap" style="display:none"><label class="form-label">{{ __('Back at') }}</label><input type="datetime-local" name="until" class="form-control"></div>
                    <div class="col-md-3"><button class="btn btn-danger" onclick="return confirm('{{ __('Close the site to visitors now?') }}')">{{ __('Start maintenance') }}</button></div>
                </form>
                <p class="small text-muted mt-2 mb-0">{{ __('Times are in the site timezone (:tz).', ['tz' => config('app.timezone')]) }}</p>
            @endif
        </div></div>
    </div>
    <form action="{{ route('admin.update-maintenance-mode') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <x-admin.form-image-preview recommended="600X380" div_id="maintenance_image_preview" label_id="maintenance_image_label" input_id="maintenance_image_upload" :image="$setting->maintenance_image" name="maintenance_image" label="{{ __('Existing Image') }}" button_label="{{ __('Update Image') }}" required="0"/>
        </div>

        <div class="form-group">
            <x-admin.form-input id="maintenance_title" name="maintenance_title" label="{{ __('Maintenance Mode Title') }}" placeholder="{{ __('Enter Maintenance Mode Title') }}" value="{{ $setting->maintenance_title }}" required="true"/>
        </div>
        <div class="form-group">
            <x-admin.form-editor id="maintenance_description" name="maintenance_description" label="{{ __('Maintenance Mode Description') }}" value="{!! $setting->maintenance_description !!}" required="true"/>
        </div>
        <x-admin.update-button :text="__('Update')" />

    </form>
</div>
