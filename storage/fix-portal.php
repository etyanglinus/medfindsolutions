<?php
$path = 'resources/views/doctor/dashboard.blade.php';
$s = str_replace("\r\n", "\n", file_get_contents($path));
$needle = '                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">'."\n".'                            <div class="card card-statistic-1">'."\n".'                                <div class="card-icon bg-warning">';
$s = str_replace($needle, "                        @if(\$subscription?->hasEntitlement('analytics'))\n".$needle, $s);
$s = str_replace("                </div>\n                <div class=\"row mb-4\">", "                        @endif\n                </div>\n                <div class=\"row mb-4\">", $s);
$lines = explode("\n", $s);
foreach ($lines as &$line) {
    if (str_contains($line, '<canvas id="profileViewsChart"')) $line = "@if(\$subscription?->hasEntitlement('analytics'))\n".$line."\n@endif";
}
unset($line);
$s = implode("\n", $lines);
$s = str_replace('                new Chart(document.getElementById("profileViewsChart"), {', '                if (document.getElementById("profileViewsChart")) new Chart(document.getElementById("profileViewsChart"), {', $s);
file_put_contents($path, $s);
$path = 'resources/views/doctor/profile/edit_profile.blade.php';
$s = file_get_contents($path);
$block = <<<'BLADE'
@if($subscription?->hasEntitlement('services'))
<div class="form-group col-12">
    <label for="service_ids">{{ __('Services') }}</label>
    <select id="service_ids" name="service_ids[]" class="form-control" multiple>
        @foreach($services as $service)
            <option value="{{ $service->id }}" @selected(in_array($service->id, old('service_ids', $doctor->services->pluck('id')->all())))>{{ $service->name }}</option>
        @endforeach
    </select>
    @error('service_ids')<div class="text-danger">{{ $message }}</div>@enderror
</div>
@endif
@if($subscription?->hasEntitlement('insurance'))
<div class="form-group col-12">
    <label for="accepted_insurance">{{ __('Accepted insurance') }}</label>
    <input id="accepted_insurance" name="accepted_insurance" class="form-control" maxlength="2000" value="{{ old('accepted_insurance', $doctor->getTranslation($code)?->accepted_insurance) }}">
</div>
@endif
BLADE;
$needle = "                                        @if(\$subscription?->hasEntitlement('whatsapp'))";
$s = str_replace($needle, $block."\n".$needle, $s);
file_put_contents($path, $s);
