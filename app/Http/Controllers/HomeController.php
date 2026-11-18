<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Models\FaqPage;
use App\Models\AboutUsPage;
use Illuminate\Http\Request;
use Modules\Day\app\Models\Day;
use Modules\Blog\app\Models\Blog;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\Service as DoctorService;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\Location;
use App\Models\ProfileView;
use App\Models\ProviderProfile;
use App\Services\PartnerPortalService;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\SupplierProduct;
use Modules\Faq\app\Models\FaqCategory;
use Modules\Service\app\Models\Service;
use Modules\Doctor\app\Models\Department;
use Modules\HomeSection\app\Models\Slider;
use Modules\HomeSection\app\Models\Counter;
use Modules\HomeSection\app\Models\Feature;
use Modules\HomeSection\app\Models\WorkSection;
use Modules\Testimonial\app\Models\Testimonial;
use Modules\HomeSection\app\Models\SectionControl;
use Modules\HomeSection\app\Models\WorkSectionFaq;
use Modules\PageBuilder\app\Models\CustomizeablePage;
use Modules\GlobalSetting\app\Models\CustomPagination;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class HomeController extends Controller {
    
    public function index() {
        $locations = Location::select('id')->with([
            'translation' => function ($query) {
                $query->select('location_id', 'name');
            },
        ])->orderBy('id', 'asc')->active()->get();
        
        $departmentsForSearch = Department::select('id')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->orderBy('slug', 'asc')->get();
        
        $doctorServicesForSearch = DoctorService::select('id', 'name')
            ->where('status', true)
            ->orderBy('name')
            ->get();
            
        $doctorsForSearch = Doctor::select('id', 'name')->orderBy('name', 'asc')->active()->get();
        $sliders = Slider::select('image','title')->active()->get();
        $home_sections = SectionControl::first();
        
        $features = Feature::select('id', 'image', 'icon')->with([
            'translation' => function ($query) {
                $query->select('feature_id', 'title', 'description');
            },
        ])->active()->get();
        
        $work = WorkSection::select('id', 'image', 'video')->with([
            'translation' => function ($query) {
                $query->select('work_section_id', 'title');
            },
        ])->first();
        
        $workFaqs = WorkSectionFaq::select('id')->with([
            'translation' => function ($query) {
                $query->select('work_section_faq_id', 'question', 'answer');
            },
        ])->where('work_section_id', $work?->id)->active()->get();

        $services = Service::select('id', 'icon', 'slug')->with([
            'translation' => function ($query) {
                $query->select('service_id', 'title', 'sort_description');
            },
        ])->active()->homepage()->get();
        
        $overviews = Counter::select('id', 'icon', 'qty')->with([
            'translation' => function ($query) {
                $query->select('counter_id', 'title');
            },
        ])->active()->get();
        
        $departments = Department::select('id', 'slug', 'thumbnail_image')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->homepage()->get();
        
        $testimonials = Testimonial::select('id', 'image')->with([
            'translation' => function ($query) {
                $query->select('testimonial_id', 'name', 'designation', 'comment');
            },
        ])->homepage()->active()->get();

        // ─────────────────────────────────────────────────────────────
        // Rotation seed: changes every 5 minutes so providers sharing
        // the same priority tier swap positions automatically.
        // All visitors see the same order within any 5-minute window.
        // ─────────────────────────────────────────────────────────────
        $rotationSeed = (int) floor(time() / 300);

        // The featured lists only change when the rotation seed does (every 5 minutes), so build them
        // once per window and language instead of loading every provider on every homepage visit.
        [$doctors, $featuredClinics, $featuredMedicalSuppliers, $featuredSupplierProducts] = Cache::remember(
            'home.featured.'.$rotationSeed.'.'.app()->getLocale().'.'.(function_exists('getSessionLanguage') ? getSessionLanguage() : ''),
            300,
            function () use ($rotationSeed) {
                // ============================================
                // Get prioritized + rotating doctors
                // ============================================
                $doctors = app(\App\Services\PublicDirectory::class)->listed('doctor', Doctor::query())->select('id', 'department_id', 'location_id', 'slug', 'name', 'image')
                    ->with([
                        'translation' => function ($query) {
                            $query->select('doctor_id', 'designations');
                        },
                        'department' => function ($query) {
                            $query->select('id');
                        },
                        'department.translation' => function ($query) {
                            $query->select('department_id', 'name');
                        },
                        'location' => function ($query) {
                            $query->select('id');
                        },
                        'location.translation' => function ($query) {
                            $query->select('location_id', 'name');
                        },
                        'socialMedia' => function ($query) {
                            $query->select('doctor_id', 'link', 'icon')->active();
                        },
                        'subscriptions' => function ($query) {
                            $query->where('status', 'approved')
                                ->whereNotNull('approved_at')
                                ->where('approved_at', '<=', now())
                                ->where(function ($q) {
                                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                });
                        }
                    ])
                    ->active()
                    ->get()
                    ->map(function ($doctor) {
                        $priority = 0;
                        foreach ($doctor->subscriptions as $subscription) {
                            // Plan-name tier
                            $planCode = strtolower($subscription->plan_code ?? $subscription->plan ?? '');
                            if (str_contains($planCode, 'executive')) {
                                $priority = max($priority, 300);
                            } elseif (str_contains($planCode, 'premium') || str_contains($planCode, 'platinum')) {
                                $priority = max($priority, 200);
                            } elseif (str_contains($planCode, 'basic')) {
                                $priority = max($priority, 100);
                            } elseif ($planCode) {
                                $priority = max($priority, 50);
                            }
                            // Entitlement bonuses (stacked on top of tier)
                            if ($subscription->hasEntitlement('homepage_rotation'))  $priority += 50;
                            if ($subscription->hasEntitlement('top_specialist_badge') || $subscription->hasEntitlement('top_doctor_badge')) $priority += 30;
                            if ($subscription->hasEntitlement('verified_badge'))      $priority += 10;
                            if ($subscription->hasEntitlement('search_visibility'))   $priority += 5;
                        }
                        $doctor->_priority = $priority;
                        return $doctor;
                    })
                    // Rotate within each priority tier using the 5-minute seed
                    ->sortByDesc(fn ($d) => $d->_priority * 1_000_000 + crc32($rotationSeed . ':doctor:' . $d->id) % 1_000_000)
                    ->values()
                    ->take(6);

                // ============================================
                // Get prioritized + rotating clinics
                // ============================================
                $featuredClinics = app(\App\Services\PublicDirectory::class)->listed('clinic', Clinic::query())->select('id', 'slug', 'name', 'specialty', 'logo', 'location')
                    ->with([
                        'subscriptions' => function ($query) {
                            $query->where('status', 'approved')
                                ->whereNotNull('approved_at')
                                ->where('approved_at', '<=', now())
                                ->where(function ($q) {
                                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                });
                        }
                    ])
                    ->active()
                    ->get()
                    ->map(function ($clinic) {
                        $priority = 0;
                        foreach ($clinic->subscriptions as $subscription) {
                            $planCode = strtolower($subscription->plan_code ?? $subscription->plan ?? '');
                            if (str_contains($planCode, 'executive'))               $priority = max($priority, 300);
                            elseif (str_contains($planCode, 'premium'))             $priority = max($priority, 200);
                            elseif (str_contains($planCode, 'basic'))               $priority = max($priority, 100);
                            elseif ($planCode)                                       $priority = max($priority, 50);
                            if ($subscription->hasEntitlement('top_clinic_badge'))  $priority += 50;
                            if ($subscription->hasEntitlement('homepage_rotation')) $priority += 30;
                            if ($subscription->hasEntitlement('search_visibility')) $priority += 5;
                        }
                        $clinic->_priority = $priority;
                        return $clinic;
                    })
                    ->sortByDesc(fn ($c) => $c->_priority * 1_000_000 + crc32($rotationSeed . ':clinic:' . $c->id) % 1_000_000)
                    ->values()
                    ->take(6);

                $partnerPortal = app(PartnerPortalService::class);
                $activeSupplierProfiles = ProviderProfile::query()->where('provider_type', ProviderProfile::TYPE_SUPPLIER)
                    ->where('status', ProviderProfile::STATUS_PUBLISHED)->whereNotNull('published_at')
                    ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($s) => $s->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                    ->with('subscriptions')->get()
                    ->filter(fn ($profile) => $partnerPortal->allows($profile, 'search_visibility') && $partnerPortal->allows($profile, 'supplier_profile'));
                $supplierIds = $activeSupplierProfiles->pluck('provider_id');

                // ============================================
                // Get prioritized + rotating suppliers
                // ============================================
                $featuredMedicalSuppliers = MedicalSupplier::query()->whereIn('id', $supplierIds)->with(['providerProfile.subscriptions', 'categories'])
                    ->latest()->get()->map(function ($supplier) use ($partnerPortal) {
                        $entitlements = $supplier->providerProfile ? $partnerPortal->entitlements($supplier->providerProfile) : [];
                        $supplier->public_entitlements = $entitlements;
                        // Priority: featured_supplier > homepage_rotation; others not shown
                        if (! empty($entitlements['featured_supplier'])) {
                            $supplier->_priority = 200;
                        } elseif (! empty($entitlements['homepage_rotation'])) {
                            $supplier->_priority = 100;
                        } else {
                            $supplier->_priority = 0;
                        }
                        return $supplier;
                    })
                    ->filter(fn ($s) => $s->_priority > 0)
                    ->sortByDesc(fn ($s) => $s->_priority * 1_000_000 + crc32($rotationSeed . ':supplier:' . $s->id) % 1_000_000)
                    ->take(6)->values();
                $featuredSupplierProducts = SupplierProduct::query()->where('approval_status', 'approved')->where('is_published', true)->where('is_featured', true)
                    ->whereHas('supplier', fn ($q) => $q->whereIn('id', $supplierIds))
                    ->with(['supplier.providerProfile.subscriptions'])->latest()->get()
                    ->map(function ($product) use ($partnerPortal) {
                        $product->supplier->public_entitlements = $product->supplier->providerProfile ? $partnerPortal->entitlements($product->supplier->providerProfile) : [];
                        return $product;
                    })->filter(fn ($product) => $product->supplier?->providerProfile && $partnerPortal->allows($product->supplier->providerProfile, 'featured_products'))
                    ->take(6)->values();

                return [$doctors, $featuredClinics, $featuredMedicalSuppliers, $featuredSupplierProducts];
            }
        );

        $feature_blog = Blog::select('id','admin_id', 'slug', 'image', 'created_at')->with([
            'admin' => function ($query) {
                $query->select('id', 'name');
            },
            'translation' => function ($query) {
                $query->select('blog_id', 'title', 'sort_description');
            },
        ])->whereHas('category', function ($query) {
            $query->active();
        })->feature()->active()->orderBy('id', 'desc')->first();
        
        $blogs = Blog::select('id','admin_id', 'slug', 'thumbnail_image', 'created_at')->with([
            'admin' => function ($query) {
                $query->select('id', 'name');
            },
            'translation' => function ($query) {
                $query->select('blog_id', 'title', 'sort_description');
            },
        ])->whereHas('category', function ($query) {
            $query->active();
        })->homepage()->active()->latest()->get();
        
        return view('patient.index', compact(
            'locations', 
            'departmentsForSearch', 
            'doctorServicesForSearch', 
            'doctorsForSearch', 
            'sliders', 
            'home_sections', 
            'features', 
            'work', 
            'workFaqs', 
            'services', 
            'overviews', 
            'departments', 
            'testimonials', 
            'doctors', 
            'featuredClinics', 
            'featuredMedicalSuppliers',
            'featuredSupplierProducts',
            'feature_blog', 
            'blogs'
        ));
    }
    
    /**
     * Public facilities / clinics listing page.
     */
    public function facilities(Request $request) {
        $keyword   = $request->filled('keyword')   ? trim($request->keyword)   : null;
        $specialty = $request->filled('specialty') ? trim($request->specialty) : null;
        $insurance = $request->filled('insurance') ? trim($request->insurance) : null;
        $feeRange  = $request->input('fee_range');
        $hours     = $request->input('hours');

        $query = app(\App\Services\PublicDirectory::class)->listed('clinic', Clinic::query())->with('latestSubscription')
            ->where('status', 1)
            ->whereHas('subscriptions', fn ($query) => $query->where('status', 'approved')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->where(fn ($q) => $q->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0)))
            ->orderBy('name');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        if ($specialty) {
            $query->where('specialty', 'like', "%{$specialty}%");
        }
        if ($insurance) {
            $query->where('insurance_accepted', 'like', "%{$insurance}%");
        }
        if ($hours === 'published') {
            $query->whereNotNull('operating_hours')->where('operating_hours', '!=', '');
        }
        if ($feeRange === 'under_2000') $query->where('consultation_fee', '<', 2000);
        if ($feeRange === '2000_5000') $query->whereBetween('consultation_fee', [2000, 5000]);
        if ($feeRange === 'over_5000') $query->where('consultation_fee', '>', 5000);

        $clinics = \App\Support\PlanRanking::apply($query, 'clinic')->paginate(12)->withQueryString();

        $specialties = Clinic::where('status', 1)
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'approved')
                    ->where(fn ($q) => $q->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0))
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->whereNotNull('specialty')
            ->orderBy('specialty')
            ->pluck('specialty')
            ->unique()
            ->values();

        $insurances = \Modules\Doctor\app\Models\InsuranceProvider::active()->pluck('name');

        return view('patient.facility.index', compact('clinics', 'specialties', 'insurances', 'keyword', 'specialty', 'insurance', 'feeRange', 'hours'));
    }

    /**
     * Public clinic / facility detail page.
     */
    public function facilityDetails(Request $request, string $slug) {
        $clinic = app(\App\Services\PublicDirectory::class)->listed('clinic', Clinic::query())->with(['galleryImages', 'branches'])
            ->where('status', 1)
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'approved')
                    ->where(fn ($q) => $q->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0))
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            })
            ->where('slug', $slug)
            ->firstOrFail();

        $this->recordProfileView('clinic', $clinic->id, $request);
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        $reviews = \Modules\Doctor\app\Models\ProviderReview::where('provider_type', 'clinic')->where('provider_id', $clinic->id)->where('status', 'published')->latest()->get();

        $related = app(\App\Services\PublicDirectory::class)->listed('clinic', Clinic::query())->where('status', 1)
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'approved')
                    ->where(fn ($q) => $q->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0))
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            })
            ->where('specialty', $clinic->specialty)
            ->where('id', '!=', $clinic->id)
            ->limit(4)
            ->get();

        return view('patient.facility.show', compact('clinic', 'related', 'subscription', 'reviews'));
    }
    
    public function aboutUs(){
        $about=AboutUsPage::select('id', 'status','about_image','background_image','mission_image','mission_status','vision_image','vision_status')->with([
            'translation' => function ($query) {
                $query->select('about_us_page_id','about_description','mission_description','vision_description');
            },
        ])->first();

        $home_sections = SectionControl::select('id','work_status')->with([
            'translation' => function ($query) {
                $query->select('section_control_id', 'work_first_heading', 'work_second_heading', 'work_description');
            },
        ])->first();

        $work = WorkSection::select('id', 'image', 'video')->with([
            'translation' => function ($query) {
                $query->select('work_section_id', 'title');
            },
        ])->first();
        $workFaqs = WorkSectionFaq::select('id')->with([
            'translation' => function ($query) {
                $query->select('work_section_faq_id', 'question', 'answer');
            },
        ])->where('work_section_id', $work?->id)->active()->get();
        $overviews = Counter::select('id', 'icon', 'qty')->with([
            'translation' => function ($query) {
                $query->select('counter_id', 'title');
            },
        ])->active()->get();

        return view('patient.about',compact('about','home_sections','work','workFaqs','overviews'));
    }

    public function service() {
        $pagination_qty = cache('CustomPagination')?->service
            ?? CustomPagination::where('section_name', 'Service')->value('item_qty')
            ?? 10;
        $services = Service::select('id', 'icon', 'slug')->with([
            'translation' => function ($query) {
                $query->select('service_id', 'title', 'sort_description');
            },
        ])->active()->paginate($pagination_qty);
        return view('patient.service.index', compact('services'));
    }

    public function serviceDetails($slug) {
        $service = Service::select('id', 'icon', 'slug')->with([
            'translation'             => function ($query) {
                $query->select('service_id', 'title', 'description', 'seo_title', 'seo_description');
            },
            'images'                  => function ($query) {
                $query->select('service_id', 'small_image', 'large_image');
            },
            'service_faq'             => function ($query) {
                $query->select('id', 'service_id')->active();
            },
            'service_faq.translation' => function ($query) {
                $query->select('service_faq_id', 'question', 'answer');
            },
            'videos'                  => function ($query) {
                $query->select('service_id', 'link');
            },
        ])->whereSlug($slug)->active()->first();
        if (!$service) {
            abort(404);
        }

        $services = Service::select('id', 'slug')->with([
            'translation' => function ($query) {
                $query->select('service_id', 'title');
            },
        ])->active()->get();

        return view('patient.service.show', compact('service', 'services'));
    }
    
    public function department() {
        $pagination_qty = cache('CustomPagination')?->department
            ?? CustomPagination::where('section_name', 'Department')->value('item_qty')
            ?? 10;
        $departments = Department::select('id', 'slug', 'thumbnail_image')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->paginate($pagination_qty);
        return view('patient.department.index', compact('departments'));
    }

    public function departmentDetails($slug) {
        $department = Department::select('id', 'slug','thumbnail_image')->with([
            'translation'                => function ($query) {
                $query->select('department_id', 'name', 'description', 'seo_title', 'seo_description');
            },
            'images'                     => function ($query) {
                $query->select('department_id', 'small_image', 'large_image');
            },
            'department_faq'             => function ($query) {
                $query->select('id', 'department_id')->active();
            },
            'department_faq.translation' => function ($query) {
                $query->select('department_faq_id', 'question', 'answer');
            },
            'videos'                     => function ($query) {
                $query->select('department_id', 'link');
            },
        ])->whereSlug($slug)->active()->first();
        if (!$department) {
            abort(404);
        }
        $departments = Department::select('id', 'slug')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->get();

        $home_sections = SectionControl::select('id')->with([
            'translation' => function ($query) {
                $query->select('section_control_id', 'department_description');
            },
        ])->first();
        $description = $home_sections?->department_description;
        $doctors = app(\App\Services\PublicDirectory::class)->listed('doctor', Doctor::query())->select('id', 'department_id', 'location_id', 'slug', 'name', 'image')->with([
            'translation'            => function ($query) {
                $query->select('doctor_id', 'designations');
            },
            'department'             => function ($query) {
                $query->select('id');
            },
            'department.translation' => function ($query) {
                $query->select('department_id', 'name');
            },
            'location'               => function ($query) {
                $query->select('id');
            },
            'location.translation'   => function ($query) {
                $query->select('location_id', 'name');
            },
            'socialMedia'            => function ($query) {
                $query->select('doctor_id', 'link', 'icon')->active();
            },
        ])->where('department_id', $department->id)->active()->get();

        return view('patient.department.show', compact('department', 'departments', 'doctors', 'description'));
    }
    
    public function doctor(Request $request) {
        $pagination_qty = cache('CustomPagination')?->doctor
            ?? CustomPagination::where('section_name', 'Doctor')->value('item_qty')
            ?? 10;
        $locations = Location::select('id')->with([
            'translation' => function ($query) {
                $query->select('location_id', 'name');
            },
        ])->orderBy('id', 'asc')->active()->get();
        $departments = Department::select('id')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->orderBy('slug', 'asc')->get();
        $doctorsForSearch = Doctor::select('id', 'name')->orderBy('name', 'asc')->active()->get();

        $keyword = $request->filled('keyword') ? trim($request->keyword) : null;
        $departmentId = $request->input('department_id');
        $insurance = $request->input('insurance');
        $feeRange = $request->input('fee_range');

        $doctors = app(\App\Services\PublicDirectory::class)->listed('doctor', Doctor::query())->select('id', 'department_id', 'location_id', 'slug', 'name', 'image', 'fee')->with([
            'latestSubscription',
            'translation'            => function ($query) {
                $query->select('doctor_id', 'designations');
            },
            'department'             => function ($query) {
                $query->select('id');
            },
            'department.translation' => function ($query) {
                $query->select('department_id', 'name');
            },
            'location'               => function ($query) {
                $query->select('id');
            },
            'location.translation'   => function ($query) {
                $query->select('location_id', 'name');
            },
            'socialMedia'            => function ($query) {
                $query->select('doctor_id', 'link', 'icon')->active();
            },
        ])->active()
            ->when($keyword, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($insurance, fn ($query) => $query->whereHas('translations', fn ($translation) => $translation->where('accepted_insurance', 'like', "%{$insurance}%")))
            ->when($feeRange === 'under_2000', fn ($query) => $query->where('fee', '<', 2000))
            ->when($feeRange === '2000_5000', fn ($query) => $query->whereBetween('fee', [2000, 5000]))
            ->when($feeRange === 'over_5000', fn ($query) => $query->where('fee', '>', 5000));
        $doctors = \App\Support\PlanRanking::apply($doctors, 'doctor')->paginate($pagination_qty)->withQueryString();

        $insurances = \Modules\Doctor\app\Models\InsuranceProvider::active()->pluck('name');

        return view('patient.doctor.index', compact('doctors', 'departments', 'locations', 'doctorsForSearch', 'insurances', 'keyword', 'departmentId', 'insurance', 'feeRange'));
    }

    public function searchDoctor(Request $request) {
        $location_id = $request->location;
        $doctor_id = $request->doctor;
        $department_id = $request->department;
        $service_id = $request->service;

        $pagination_qty = cache('CustomPagination')?->doctor
            ?? CustomPagination::where('section_name', 'Doctor')->value('item_qty')
            ?? 10;

        $doctors = app(\App\Services\PublicDirectory::class)->listed('doctor', Doctor::query())->select('id', 'department_id', 'location_id', 'slug', 'name', 'image')->with([
            'translation'            => function ($query) {
                $query->select('doctor_id', 'designations');
            },
            'department'             => function ($query) {
                $query->select('id');
            },
            'department.translation' => function ($query) {
                $query->select('department_id', 'name');
            },
            'location'               => function ($query) {
                $query->select('id');
            },
            'location.translation'   => function ($query) {
                $query->select('location_id', 'name');
            },
            'socialMedia'            => function ($query) {
                $query->select('doctor_id', 'link', 'icon')->active();
            },
        ])->orderBy('name', 'asc')->active();

        if ($location_id) {
            $doctors = $doctors->where('location_id', $location_id);
        }

        if ($department_id) {
            $doctors = $doctors->where('department_id', $department_id);
        }

        if ($doctor_id) {
            $doctors = $doctors->where('id', $doctor_id);
        }

        if ($service_id) {
            $doctors = $doctors->whereHas('services', function ($query) use ($service_id) {
                $query->where('services.id', $service_id);
            });
        }

        $doctors = \App\Support\PlanRanking::apply($doctors, 'doctor')->paginate($pagination_qty);
        $doctors = $doctors->appends($request->all());

        $locations = Location::select('id')->with([
            'translation' => function ($query) {
                $query->select('location_id', 'name');
            },
        ])->orderBy('id', 'asc')->active()->get();
        $departments = Department::select('id')->with([
            'translation' => function ($query) {
                $query->select('department_id', 'name');
            },
        ])->active()->orderBy('slug', 'asc')->get();
        $doctorsForSearch = Doctor::select('id', 'name')->orderBy('name', 'asc')->active()->get();
        $insurances = \Modules\Doctor\app\Models\InsuranceProvider::active()->pluck('name');

        return view('patient.doctor.index', compact('doctors', 'departments', 'locations', 'doctorsForSearch', 'insurances', 'location_id', 'doctor_id', 'department_id'));
    }

    public function doctorDetails(Request $request, $slug) {
        $doctor = app(\App\Services\PublicDirectory::class)->listed('doctor', Doctor::query())->select('id', 'department_id', 'location_id', 'slug', 'name', 'email', 'phone', 'whatsapp', 'fee','years_of_experience', 'image')->with([
            'translation'            => function ($query) {
                $query->select('doctor_id', 'seo_title', 'seo_description', 'designations', 'about', 'address', 'educations', 'experience', 'qualifications');
            },
            'department'             => function ($query) {
                $query->select('id');
            },
            'department.translation' => function ($query) {
                $query->select('department_id', 'name');
            },
            'location', 'schedules'=> function ($query) {
                $query->select('doctor_id','day_id', 'start_time', 'end_time')->active();
            },
            'socialMedia'            => function ($query) {
                $query->select('doctor_id', 'link', 'icon')->active();
            },
        ])->active()
            ->whereSlug($slug)
            ->first();
        if (!$doctor) {
            abort(404);
        }
        $this->recordProfileView('doctor', $doctor->id, $request);
        $days = Day::select('id')->with([
            'translation' => function ($query) {
                $query->select('day_id', 'title');
            },
        ])->active()->get();

        $subscription = app(SubscriptionAccessService::class)->activeDoctorSubscription($doctor);
        $reviews = \Modules\Doctor\app\Models\ProviderReview::where('provider_type', 'doctor')->where('provider_id', $doctor->id)->where('status', 'published')->latest()->get();

        return view('patient.doctor.show', compact('doctor', 'days', 'subscription', 'reviews'));
    }
    
    public function testimonial() {
        $pagination_qty = cache('CustomPagination')?->testimonial
            ?? CustomPagination::where('section_name', 'Testimonial')->value('item_qty')
            ?? 10;
        $testimonials = Testimonial::select('id', 'image')->with([
            'translation' => function ($query) {
                $query->select('testimonial_id', 'name', 'designation', 'comment');
            },
        ])->active()->paginate($pagination_qty);
        return view('patient.testimonial', compact('testimonials'));
    }
    
    public function faq() {
        $faq_page = FaqPage::first();
        $faqCategories = FaqCategory::select('id')->with([
            'translation' => function ($query) {
                $query->select('faq_category_id', 'title');
            },
            'faq_list'             => function ($query) {
                $query->select('id','faq_category_id')->active();
            },
            'faq_list.translation' => function ($query) {
                $query->select('faq_id', 'question', 'answer');
            },
        ])->active()->get();
        return view('patient.faq', compact('faq_page','faqCategories'));
    }

    public function privacyPolicy() {
        $customPage = CustomizeablePage::with('translation')->whereSlug('privacy-policy')->whereStatus(true)->first();
        if ($customPage) {
            return view('patient.privacy-policy', compact('customPage'));
        }
        abort(404);
    }

    public function termsCondition() {
        $customPage = CustomizeablePage::with('translation')->whereSlug('terms-contidions')->whereStatus(true)->first();
        if ($customPage) {
            return view('patient.terms-condition', compact('customPage'));
        }
        abort(404);
    }
    
    public function customPage($slug) {
        $customPage = CustomizeablePage::with('translation')->whereStatus(true)->whereSlug($slug)->first();
        if ($customPage) {
            return view('patient.custom-page', compact('customPage'));
        }
        abort(404);
    }

    private function recordProfileView(string $profileType, int $profileId, Request $request): void {
        ProfileView::firstOrCreate([
            'profile_type' => $profileType,
            'profile_id'   => $profileId,
            'viewed_on'    => today()->toDateString(),
            'session_hash' => hash('sha256', $request->session()->getId()),
        ]);
    }
}
