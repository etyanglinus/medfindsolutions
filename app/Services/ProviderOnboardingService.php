<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\app\Models\ProviderDocument;

class ProviderOnboardingService
{
    public function status(Model $client, string $type): array
    {
        $missing = [];
        $fields = match ($type) {
            'doctor' => ['name', 'email', 'phone', 'department_id', 'location_id', 'years_of_experience'],
            'clinic' => ['name', 'email', 'phone', 'specialty', 'licensing_body', 'description', 'address', 'educations', 'experience', 'qualifications'],
            'school' => ['name', 'official_email', 'phone', 'institution_type', 'ownership', 'about', 'location'],
            'supplier' => ['name', 'official_email', 'phone', 'description', 'address'],
        };
        foreach ($fields as $field) if (!$this->hasContent($client->getAttribute($field))) $missing[] = ucwords(str_replace('_', ' ', $field));
        if ($type === 'doctor') {
            $translations = $client->translations()->get();
            $required = ['licensing_body', 'about', 'address', 'educations', 'experience', 'qualifications'];
            $complete = $translations->contains(fn ($row) => collect($required)->every(fn ($field) => $this->hasContent($row->$field)));
            if (!$complete) $missing[] = __('Professional profile details');
        }
        // Sections the package shows publicly (and a real description) are required too.
        foreach (app(ProfileRequirements::class)->missing($type, $client) as $label) $missing[] = $label;
        $missing = array_values(array_unique($missing));
        $photo = $type === 'doctor' ? filled($client->image) : filled($client->logo);
        $document = ProviderDocument::where('provider_type', $type)->where('provider_id', $client->id)
            ->whereIn('status', ['pending', 'approved'])->whereNotNull('file_path')->where('file_path', '!=', '')->exists();
        $profileUrl = match ($type) {
            'doctor' => route('doctor.edit-profile'), 'clinic' => route('clinic.edit-profile'),
            default => route('website.partner.profile', ['type' => $type]),
        };
        $documentsUrl = in_array($type, ['doctor', 'clinic']) ? route($type.'.documents.index') : route('website.partner.documents.index', ['type' => $type]);
        return ['complete' => !$missing && $photo && $document, 'missing' => $missing, 'photo' => $photo, 'document' => $document,
            'profile_url' => $profileUrl, 'documents_url' => $documentsUrl, 'next_url' => $missing || !$photo ? $profileUrl : $documentsUrl];
    }

    private function hasContent($value): bool
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/[\s\x{00A0}\x{200B}]+/u', '', $text) !== '';
    }
}
