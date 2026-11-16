<?php

namespace App\Http\Controllers;

use App\Services\PublicDirectory;
use App\Support\KenyaPlaces;
use App\Support\PlanRanking;
use App\Support\StructuredData;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Search landing pages such as "Cardiologists in Kenya", "Cardiologists in Westlands",
 * "Medical facilities in Kilimani", "Medical suppliers in Kiambu County" and "Healthcare schools in
 * Nairobi". A page only exists when it lists at least one live provider (otherwise 404), so search
 * engines never see empty pages. Providers are ordered by plan, as in the directories.
 */
class SeoLandingController extends Controller
{
    private const PER_PAGE = 24;

    public function __construct(private PublicDirectory $directory) {}

    public function specialty(string $specialty): View
    {
        $row = $this->directory->specialties()->get($specialty) ?? abort(404);
        $department = $row['department'];
        $plural = PublicDirectory::specialtyPlural($department->name);
        $query = $this->directory->doctors()->where('department_id', $department->id);

        return $this->render('doctor', $query, [
            'h1' => __(':plural in Kenya', ['plural' => $plural]),
            'title' => __(':plural in Kenya | Verified specialists', ['plural' => $plural]),
            'intro' => __('Find :count :plural listed on MedFind. Compare profiles, qualifications and working hours, then call, WhatsApp or send an enquiry directly.', ['count' => $row['count'], 'plural' => Str::lower($plural)]),
            'crumbs' => [[__('Specialists'), route('website.doctors')], [$plural, null]],
            'links' => $this->placeLinks($this->directory->places('doctor', $department->id), fn ($slug) => route('website.seo.specialty-place', [$specialty, $slug]), __(':plural by area', ['plural' => $plural])),
            'related' => $this->specialtyLinks($specialty),
        ]);
    }

    public function specialtyPlace(string $specialty, string $place): View
    {
        $row = $this->directory->specialties()->get($specialty) ?? abort(404);
        $placeName = KenyaPlaces::name($place) ?? abort(404);
        $department = $row['department'];
        $ids = $this->directory->ids('doctor', $placeName, $department->id);
        abort_if($ids->isEmpty(), 404);
        $plural = PublicDirectory::specialtyPlural($department->name);

        return $this->render('doctor', $this->directory->doctors()->whereIn('id', $ids), [
            'h1' => __(':plural in :place', ['plural' => $plural, 'place' => $placeName]),
            'title' => __(':plural in :place, Kenya', ['plural' => $plural, 'place' => $placeName]),
            'intro' => __(':count :plural on MedFind practise in or near :place. Compare their profiles and contact them directly.', ['count' => $ids->count(), 'plural' => Str::lower($plural), 'place' => $placeName]),
            'crumbs' => [[__('Specialists'), route('website.doctors')], [$plural, route('website.seo.specialty', $specialty)], [$placeName, null]],
            'links' => $this->placeLinks(collect($this->directory->places('doctor', $department->id))->except($place)->all(), fn ($slug) => route('website.seo.specialty-place', [$specialty, $slug]), __('Other areas')),
            'related' => [],
        ]);
    }

    public function facilities(string $place): View
    {
        return $this->placePage('clinic', $place, __('Medical facilities'), route('website.facilities'), 'website.seo.facilities',
            __('Hospitals, clinics and diagnostic centres listed on MedFind. Compare services, opening hours and insurance accepted, then contact the facility directly.'));
    }

    public function suppliers(string $place): View
    {
        return $this->placePage('supplier', $place, __('Medical suppliers'), route('website.medical-suppliers'), 'website.seo.suppliers',
            __('Medical equipment and supply companies that serve this area. Browse their products and request a quotation directly.'));
    }

    public function schools(string $place): View
    {
        return $this->placePage('school', $place, __('Healthcare schools'), route('website.healthcare-courses'), 'website.seo.schools',
            __('Nursing, clinical medicine and allied-health colleges listed on MedFind. Compare courses and intakes, then enquire directly.'));
    }

    private function placePage(string $type, string $place, string $label, string $directoryUrl, string $routeName, string $blurb): View
    {
        $placeName = KenyaPlaces::name($place) ?? abort(404);
        if ($type === 'supplier') abort_unless(array_key_exists($place, KenyaPlaces::counties()), 404);
        $ids = $this->directory->ids($type, $placeName);
        abort_if($ids->isEmpty(), 404);
        $where = $type === 'supplier' ? __(':place County', ['place' => $placeName]) : $placeName;

        return $this->render($type, $this->directory->query($type)->whereIn('id', $ids), [
            'h1' => __(':label in :place', ['label' => $label, 'place' => $where]),
            'title' => __(':label in :place, Kenya', ['label' => $label, 'place' => $where]),
            'intro' => __(':count listed. :blurb', ['count' => $ids->count(), 'blurb' => $blurb]),
            'crumbs' => [[$label, $directoryUrl], [$where, null]],
            'links' => $this->placeLinks(collect($this->directory->places($type))->except($place)->all(), fn ($slug) => route($routeName, $slug), __('Other areas')),
            'related' => [],
        ]);
    }

    private function render(string $type, $query, array $page): View
    {
        $query = match ($type) {
            'doctor' => $query->with(['department.translation', 'translation']),
            default => $query,
        };
        $results = PlanRanking::apply($query, $type)->paginate(self::PER_PAGE)->withQueryString();
        $cards = $results->getCollection()->map(fn ($item) => $this->card($type, $item));

        $crumbs = array_merge([[__('Home'), route('home')]], $page['crumbs']);
        $structured = [
            StructuredData::breadcrumbs(collect($crumbs)->map(fn ($c) => ['name' => $c[0], 'url' => $c[1] ?? url()->current()])->all()),
            StructuredData::itemList($page['h1'], $cards->map(fn ($c) => ['name' => $c['name'], 'url' => $c['url']])),
        ];

        return view('patient.seo.landing', $page + compact('results', 'cards', 'crumbs', 'structured') + [
            'description' => Str::limit($page['intro'], 155),
        ]);
    }

    private function card(string $type, $item): array
    {
        return match ($type) {
            'doctor' => ['name' => $item->name, 'url' => route('website.doctor.details', $item->slug), 'subtitle' => $item->department?->name, 'place' => Str::limit(strip_tags((string) $item->address), 60), 'image' => $item->image ? url($item->image) : null, 'tier' => (int) $item->plan_tier],
            'clinic' => ['name' => $item->name, 'url' => route('website.facility.details', $item->slug), 'subtitle' => $item->specialty, 'place' => $item->location, 'image' => $this->image($item->logo), 'tier' => (int) $item->plan_tier],
            'supplier' => ['name' => $item->name, 'url' => route('website.medical-supplier', $item->slug), 'subtitle' => collect((array) $item->counties_served)->implode(', '), 'place' => $item->address, 'image' => $this->image($item->logo), 'tier' => (int) $item->plan_tier],
            'school' => ['name' => $item->name, 'url' => route('website.healthcare-institution', $item->slug), 'subtitle' => $item->institution_type, 'place' => $item->location, 'image' => $this->image($item->logo), 'tier' => (int) $item->plan_tier],
        };
    }

    private function image(?string $path): ?string
    {
        if (!$path) return null;
        if (Str::startsWith($path, ['http://', 'https://', '//'])) return $path;
        return preg_match('#^(partner-|supplier-|clinic-gallery)#', $path) ? asset('uploads/store/'.$path) : asset($path);
    }

    private function placeLinks(array $places, callable $url, string $heading): array
    {
        return ['heading' => $heading, 'items' => collect($places)->take(30)->map(fn ($p, $slug) => ['label' => $p['name'], 'count' => $p['count'], 'url' => $url($slug)])->values()->all()];
    }

    private function specialtyLinks(string $except): array
    {
        return ['heading' => __('Other specialties'), 'items' => $this->directory->specialties()->except($except)->take(30)
            ->map(fn ($row, $slug) => ['label' => PublicDirectory::specialtyPlural($row['department']->name), 'count' => $row['count'], 'url' => route('website.seo.specialty', $slug)])->values()->all()];
    }
}
