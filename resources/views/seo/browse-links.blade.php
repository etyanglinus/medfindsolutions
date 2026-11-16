{{-- "Browse by" links to SEO landing pages that have content. Expects $browseType: doctor|clinic|supplier|school --}}
@php
    $seoDirectory = app(\App\Services\PublicDirectory::class);
    $browseBlocks = [];
    if ($browseType === 'doctor') {
        $browseBlocks[] = ['heading' => __('Browse specialists by specialty'), 'items' => $seoDirectory->specialties()->take(40)
            ->map(fn ($row, $slug) => ['label' => \App\Services\PublicDirectory::specialtyPlural($row['department']->name), 'count' => $row['count'], 'url' => route('website.seo.specialty', $slug)])->values()->all()];
    } else {
        $routeName = ['clinic' => 'website.seo.facilities', 'supplier' => 'website.seo.suppliers', 'school' => 'website.seo.schools'][$browseType];
        $browseBlocks[] = ['heading' => $browseType === 'supplier' ? __('Browse suppliers by county') : __('Browse by area'), 'items' => collect($seoDirectory->places($browseType))->take(40)
            ->map(fn ($p, $slug) => ['label' => $p['name'], 'count' => $p['count'], 'url' => route($routeName, $slug)])->values()->all()];
    }
@endphp
@foreach($browseBlocks as $block)
    @if(!empty($block['items']))
        <section class="container pb-5">
            <h2 class="h5 fw-bold">{{ $block['heading'] }}</h2>
            <ul class="list-unstyled d-flex flex-wrap gap-2 mt-3 mb-0">
                @foreach($block['items'] as $link)
                    <li><a class="btn btn-sm btn-light border rounded-pill" href="{{ $link['url'] }}">{{ $link['label'] }} <span class="text-muted">({{ $link['count'] }})</span></a></li>
                @endforeach
            </ul>
        </section>
    @endif
@endforeach
