{{-- Required photo/logo at sign-up. Expects $field (input name), $id, $label. --}}
<div class="col-12">
    <label class="form-label fw-semibold small text-secondary" for="{{ $id }}">{{ $label }} <span class="text-danger">*</span></label>
    <div class="d-flex align-items-center gap-3">
        <div class="mf-photo-preview" id="{{ $id }}-preview" style="width:64px;height:58px;border-radius:12px;border:1px dashed #cbd5e1;background:#f8fafc center/cover no-repeat;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-camera" aria-hidden="true"></i></div>
        <div class="flex-grow-1">
            <input type="file" name="{{ $field }}" id="{{ $id }}" accept="image/jpeg,image/png,image/webp" required
                class="form-control rounded-3 @error($field) is-invalid @enderror"
                onchange="(function(i){var p=document.getElementById(i.id+'-preview');if(!i.files||!i.files[0]){p.style.backgroundImage='';p.innerHTML='<i class=&quot;fas fa-camera&quot;></i>';return;}var r=new FileReader();r.onload=function(e){p.style.backgroundImage='url('+e.target.result+')';p.innerHTML='';};r.readAsDataURL(i.files[0]);})(this)">
            <small class="text-muted d-block mt-1">{{ __('Recommended 300 × 270 px. JPG, PNG or WebP, up to 2 MB. It is shown on your public profile.') }}</small>
            @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
