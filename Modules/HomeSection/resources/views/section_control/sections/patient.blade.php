<div class="tab-pane fade" id="patient_tab" role="tabpanel">
    <form action="{{ route('admin.section-control.update', ['code' => $code]) }}" method="POST">
        @csrf
        @method('PUT')
        <x-admin.form-input type="hidden" name="tab" value="patient_section" />
        <div class="form-group">
            <x-admin.form-input data-translate="true" id="patient_first_heading" name="patient_first_heading" label="{{ __('First Heading') }}" value="{{ $section_control?->getTranslation($code)?->patient_first_heading }}" required="true"/>
        </div>
        <div class="form-group">
            <x-admin.form-input data-translate="true" id="patient_second_heading" name="patient_second_heading" label="{{ __('Second Heading') }}" value="{{ $section_control?->getTranslation($code)?->patient_second_heading }}" required="true"/>
        </div>
        <div class="form-group">
            <x-admin.form-textarea data-translate="true" id="patient_description" name="patient_description" label="{{ __('Description') }}"
                placeholder="{{ __('Enter description') }}"
                value="{{ $section_control?->getTranslation($code)?->patient_description }}" maxlength="1000" required="true"/>
        </div>
        <div class="form-group {{$code == $languages->first()->code ? '': 'd-none'}}">
            <x-admin.form-input type="number" id="patient_how_many" name="patient_how_many" label="{{ __('How Many') }}" value="{{ $section_control?->patient_how_many ?? 4 }}" required="true"/>
        </div>
        <div class="form-group {{$code == $languages->first()->code ? '': 'd-none'}}">
            <x-admin.form-switch name="patient_status" label="{{ __('Status') }}" :checked="$section_control?->patient_status == 1"/>
        </div>
        <x-admin.update-button :text="__('Update')" />
    </form>
</div>