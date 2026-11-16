<?php

namespace App\Http\Controllers;

use App\Services\PublicDirectory;
use App\Support\KenyaPlaces;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\app\Models\Blog;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\HealthcareInstitution\app\Models\HealthcareCourse;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\SupplierProduct;

/**
 * /sitemap.xml lists every public page that has content: directories, SEO landing pages with at
 * least one provider, listed profiles, approved courses and products, and blog posts. Built at
 * most once an hour. /robots.txt points crawlers to it and keeps them out of the portals.
 */
class SitemapController extends Controller
{
    public function sitemap(PublicDirectory $directory): Response
    {
        $xml = Cache::remember('seo.sitemap.xml', now()->addHour(), fn () => $this->build($directory));
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /doctor/',
            'Disallow: /clinic/',
            'Disallow: /partner/',
            'Disallow: /provider/',
            'Disallow: /listing-',
            'Disallow: /setup',
            'Allow: /provider/register',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];
        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function build(PublicDirectory $directory): string
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null, string $priority = '0.5') use (&$urls) {
            $urls[$loc] = ['loc' => $loc, 'lastmod' => $lastmod ? \Illuminate\Support\Carbon::parse($lastmod)->toDateString() : null, 'priority' => $priority];
        };

        $add(route('home'), null, '1.0');
        foreach (['website.doctors', 'website.facilities', 'website.medical-suppliers', 'website.healthcare-courses'] as $name) $add(route($name), null, '0.9');
        foreach (['/blog', '/about-us', '/contact-us', '/faq', '/privacy-policy', '/terms-condition'] as $path) $add(url($path), null, '0.3');

        // Landing pages that list at least one provider.
        foreach ($directory->specialties() as $slug => $row) {
            $add(route('website.seo.specialty', $slug), null, '0.8');
            foreach (array_keys($directory->places('doctor', $row['department']->id)) as $place) $add(route('website.seo.specialty-place', [$slug, $place]), null, '0.7');
        }
        foreach (['clinic' => 'website.seo.facilities', 'supplier' => 'website.seo.suppliers', 'school' => 'website.seo.schools'] as $type => $name) {
            foreach (array_keys($directory->places($type)) as $place) $add(route($name, $place), null, '0.7');
        }

        // Profiles of listed providers.
        Doctor::whereIn('id', $directory->ids('doctor'))->get(['slug', 'updated_at'])->each(fn ($d) => $add(route('website.doctor.details', $d->slug), $d->updated_at, '0.6'));
        Clinic::whereIn('id', $directory->ids('clinic'))->get(['slug', 'updated_at'])->each(fn ($c) => $add(route('website.facility.details', $c->slug), $c->updated_at, '0.6'));
        $suppliers = MedicalSupplier::whereIn('id', $directory->ids('supplier'))->get(['id', 'slug', 'updated_at']);
        $suppliers->each(fn ($s) => $add(route('website.medical-supplier', $s->slug), $s->updated_at, '0.6'));
        $schools = HealthcareInstitution::whereIn('id', $directory->ids('school'))->get(['id', 'slug', 'updated_at']);
        $schools->each(fn ($s) => $add(route('website.healthcare-institution', $s->slug), $s->updated_at, '0.6'));

        HealthcareCourse::whereIn('healthcare_institution_id', $schools->pluck('id'))->where('approval_status', 'approved')->get(['slug', 'updated_at'])
            ->each(fn ($c) => $add(route('website.healthcare-course', $c->slug), $c->updated_at, '0.5'));
        $supplierSlugs = $suppliers->pluck('slug', 'id');
        SupplierProduct::whereIn('medical_supplier_id', $suppliers->pluck('id'))->where('approval_status', 'approved')->where('is_published', true)->get(['medical_supplier_id', 'slug', 'updated_at'])
            ->each(fn ($p) => $add(route('website.medical-supplier.product', [$supplierSlugs[$p->medical_supplier_id], $p->slug]), $p->updated_at, '0.4'));

        Blog::query()->active()->get(['slug', 'updated_at'])->each(fn ($b) => $add(route('website.blog.details', $b->slug), $b->updated_at, '0.4'));

        $body = collect($urls)->map(fn ($u) => '<url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '').'<priority>'.$u['priority'].'</priority></url>')->implode("\n");
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$body."\n</urlset>\n";
    }
}
