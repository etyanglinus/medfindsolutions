{{-- add prescribe row --}}
<div id="hiddenPrescribeRow" class="d-none">
    <div class="row mt-3 delete-prescribe-row">
        <div class="form-group col-2">
            <x-admin.form-select name="medicine_type[]" class="form-select" label="{{ __('Medicine Type') }} ">
                @foreach ($medicineTypes as $type)
                    <x-admin.select-option value="{{ $type->type }}" text="{{ $type->type }}" />
                @endforeach
            </x-admin.form-select>
        </div>
        <div class="form-group col-3">
            <x-admin.form-select name="medicine_name[]" class="form-select" label="{{ __('Medicine') }} ">
                @foreach ($medicines as $medicine)
                    <x-admin.select-option value="{{ $medicine->name }}" text="{{ $medicine->name }}" />
                @endforeach
            </x-admin.form-select>
        </div>
        <div class="form-group col-2">
            <x-admin.form-select name="dosage[]" class="form-select" label="{{ __('Dosage') }} ">
                @foreach ($dosages as $dosage)
                    <x-admin.select-option value="{{ $dosage }}" text="{{ $dosage }}" />
                @endforeach
            </x-admin.form-select>
        </div>
        <div class="form-group col-2">
            <x-admin.form-select name="day[]" class="form-select" label="{{ __('Days') }} ">
                @for ($i = 1; $i <= 90; $i++)
                    <x-admin.select-option value="{{ $i }}" text="{{ $i }}" />
                @endfor
            </x-admin.form-select>
        </div>
        <div class="form-group col-3">
            <label>{{ __('Time') }}</label>
            <div class="input-group">
                <select name="time[]"class="form-select wsus-select">
                    <option value="After Meal">{{ __('After Meal') }}</option>
                    <option value="Before Meal">{{ __('Before Meal') }}</option>
                </select>
                <div class="input-group-append">
                    <div class="input-group-text wsus-toggle-medicine-row bg-danger text-white" id="removePrescribeRow">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
