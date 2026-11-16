<?php

namespace App\Enums;

enum RouteList {
    public static function getAll():object {
        $route_list = [
            (object)[
                'name'       => __('Dashboard'),
                'route'      => self::url('admin.dashboard'),
                'permission' => 'dashboard.view',
            ],
            (object)[
                'name'       => __('Profile'),
                'route'      => self::url('admin.edit-profile'),
                'permission' => 'admin.profile.view',
            ],
            (object)[
                'name'       => __('Order History'),
                'route'      => self::url('admin.orders'),
                'permission' => 'order.management',
            ],
            (object)[
                'name'       => __('Pending Order'),
                'route'      => self::url('admin.pending-orders'),
                'permission' => 'order.management',
            ],
            (object)[
                'name'       => __('All Appointment'),
                'route'      => self::url('admin.appointment.index'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('New Appointments'),
                'route'      => self::url('admin.appointment.new'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('Pending Appointments'),
                'route'      => self::url('admin.appointment.pending'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('Prescription'),
                'route'      => self::url('admin.prescribe'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('Payment History'),
                'route'      => self::url('admin.payment.history'),
                'permission' => 'payment.view',
            ],
            (object)[
                'name'       => __('Schedules'),
                'route'      => self::url('admin.schedule.index', ['code' => getSessionLanguage()]),
                'permission' => 'schedule.view',
            ],
            (object)[
                'name'       => __('Days'),
                'route'      => self::url('admin.day.index', ['code' => getSessionLanguage()]),
                'permission' => 'day.view',
            ],
            (object)[
                'name'       => __('Habits'),
                'route'      => self::url('admin.habit.index', ['code' => getSessionLanguage()]),
                'permission' => 'habit.view',
            ],
            (object)[
                'name'       => __('Upcomming Meeting'),
                'route'      => self::url('admin.upcomming-meeting'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('Previous Meeting'),
                'route'      => self::url('admin.previous-meeting'),
                'permission' => 'appointment.view',
            ],
            (object)[
                'name'       => __('All Patients'),
                'route'      => self::url('admin.all-customers'),
                'permission' => 'patient.view',
            ],
            (object)[
                'name'       => __('Active Patient'),
                'route'      => self::url('admin.active-customers'),
                'permission' => 'patient.view',
            ],
            (object)[
                'name'       => __('Non verified'),
                'route'      => self::url('admin.non-verified-customers'),
                'permission' => 'patient.view'],
            (object)[
                'name'       => __('Banned Patient'),
                'route'      => self::url('admin.banned-customers'),
                'permission' => 'patient.view',
            ],
            (object)[
                'name'       => __('Patient Send bulk mail'),
                'route'      => self::url('admin.send-bulk-mail'),
                'permission' => 'patient.view',
            ],
            (object)[
                'name'       => __('Department'),
                'route'      => self::url('admin.department.index'),
                'permission' => 'department.view',
            ],
            (object)[
                'name'       => __('Location'),
                'route'      => self::url('admin.location.index', ['code' => getSessionLanguage()]),
                'permission' => 'location.view',
            ],
            (object)[
                'name'       => __('Doctor'),
                'route'      => self::url('admin.doctor.index'),
                'permission' => 'doctor.view',
            ],
            (object)[
                'name'       => __('Medicine'),
                'route'      => self::url('admin.medicine.index',['code' => getSessionLanguage()]),
                'permission' => 'medicine.view',
            ],
            (object)[
                'name'       => __('Medicine Type'),
                'route'      => self::url('admin.medicine.type.index',['code' => getSessionLanguage()]),
                'permission' => 'medicine.type.view',
            ],
            (object)[
                'name'       => __('Services'),
                'route'      => self::url('admin.service.index'),
                'permission' => 'service.view',
            ],
            (object)[
                'name'       => __('Category List'),
                'route'      => self::url('admin.blog-category.index'),
                'permission' => 'blog.category.view',
            ],
            (object)[
                'name'       => __('Post List'),
                'route'      => self::url('admin.blogs.index'),
                'permission' => 'blog.view',
            ],
            (object)[
                'name'       => __('Post Comments'),
                'route'      => self::url('admin.blog-comment.index'),
                'permission' => 'blog.comment.view',
            ],
            (object)[
                'name'       => __('Menu Builder'),
                'route'      => self::url('admin.custom-menu.index'),
                'permission' => 'menu.view',
            ],
            (object)[
                'name'       => __('Sliders'),
                'route'      => self::url('admin.slider.index'),
                'permission' => 'slider.view',
            ],
            (object)[
                'name'       => __('Features'),
                'route'      => self::url('admin.feature.index'),
                'permission' => 'feature.view',
            ],
            (object)[
                'name'       => __('Work Section'),
                'route'      => self::url('admin.work-section.index', ['code' => getSessionLanguage()]),
                'permission' => 'work.section.view',
            ],
            (object)[
                'name'       => __('Overview'),
                'route'      => self::url('admin.counter.index'),
                'permission' => 'counter.view',
            ],
            (object)[
                'name'       => __('Partners'),
                'route'      => self::url('admin.partner.index'),
                'permission' => 'partner.view',
            ],
            (object)[
                'name'       => __('Section Controls'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'feature_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Feature Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'feature_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Work Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'work_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Service Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'service_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Department Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'department_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Testimonial Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'patient_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Doctor Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'doctor_tab',
            ],
            (object)[
                'name'       => __('Section Controls') . ' > ' . __('Blog Section'),
                'route'      => self::url('admin.section-control.index', ['code' => getSessionLanguage()]),
                'permission' => 'section.view',
                'tab'        => 'blog_tab',
            ],
            (object)[
                'name'       => __('About Us'),
                'route'      => self::url('admin.pages.about-us.index', ['code' => getSessionLanguage()]),
                'permission' => 'page.aboutus.view',
            ],
            (object)[
                'name'       => __('Customizable Page'),
                'route'      => self::url('admin.custom-pages.index'),
                'permission' => 'page.view',
            ],
            (object)[
                'name'       => __('FAQS'),
                'route'      => self::url('admin.faq-category.index'),
                'permission' => 'faq.view',
            ],
            (object)[
                'name'       => __('Social Links'),
                'route'      => self::url('admin.social-link.index'),
                'permission' => 'social.link.management',
            ],
            (object)[
                'name'       => __('Withdraw Method'),
                'route'      => self::url('admin.withdraw-method.index'),
                'permission' => 'withdraw.management',
            ],
            (object)[
                'name'       => __('Withdraw List'),
                'route'      => self::url('admin.withdraw-list'),
                'permission' => 'withdraw.management',
            ],
            (object)[
                'name'       => __('Pending Withdraw'),
                'route'      => self::url('admin.pending-withdraw-list'),
                'permission' => 'withdraw.management',
            ],
            (object)[
                'name'       => __('Subscriber List'),
                'route'      => self::url('admin.subscriber-list'),
                'permission' => 'newsletter.view',
            ],
            (object)[
                'name'       => __('Subscriber Send bulk mail'),
                'route'      => self::url('admin.send-mail-to-newsletter'),
                'permission' => 'newsletter.mail',
            ],
            (object)[
                'name'       => __('Subscriber Content'),
                'route'      => self::url('admin.subscriber-content', ['code' => getSessionLanguage()]),
                'permission' => 'newsletter.content.view',
            ],
            (object)[
                'name'       => __('Testimonial'),
                'route'      => self::url('admin.testimonial.index'),
                'permission' => 'testimonial.view',
            ],
            (object)[
                'name'       => __('Prescription Contact'),
                'route'      => self::url('admin.prescription-contact'),
                'permission' => 'contact.info.view',
            ],
            (object)[
                'name'       => __('Contact Messages'),
                'route'      => self::url('admin.contact-messages'),
                'permission' => 'contact.message.view',
            ],
            (object)[
                'name'       => __('Contact Info'),
                'route'      => self::url('admin.contact-info', ['code' => getSessionLanguage()]),
                'permission' => 'contact.info.view',
            ],
            (object)[
                'name'       => __('App Settings') . ' > ' . __('On Boarding Screens'),
                'route'      => self::url('admin.app.screen.index'),
                'permission' => 'app.management',
                'tab'        => 'on_boarding_tab',
            ],
            (object)[
                'name'       => __('App Settings') . ' > ' . __('Banner Image'),
                'route'      => self::url('admin.app.screen.index'),
                'permission' => 'app.management',
                'tab'        => 'banner_tab',
            ],
            (object)[
                'name'       => __('General Settings'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'general_tab',
            ],
            (object)[
                'name'       => __('Blog Comment'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'comment_tab',
            ],
            (object)[
                'name'       => __('Time & Date Setting'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'website_tab',
            ],
            (object)[
                'name'       => __('Logo & Favicon'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'logo_favicon_tab',
            ],
            (object)[
                'name'       => __('Preloader'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'preloader_tab',
            ],
            (object)[
                'name'       => __('Theme Color'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'color_tab',
            ],
            (object)[
                'name'       => __('Cookie Consent'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'cookie_consent_tab',
            ],
            (object)[
                'name'       => __('Custom Pagination'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'custom_pagination_tab',
            ],
            (object)[
                'name'       => __('Default avatar'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'default_avatar_tab',
            ],
            (object)[
                'name'       => __('Breadcrumb image'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'breadcrump_img_tab',
            ],
            (object)[
                'name'       => __('404 Page'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'error_page_img_tab',
            ],
            (object)[
                'name'       => __('Maintenance Mode'),
                'route'      => self::url('admin.general-setting'),
                'permission' => 'setting.view',
                'tab'        => 'mmaintenance_mode_tab',
            ],
            (object)[
                'name'       => __('Credential Settings'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'google_recaptcha_tab',
            ],
            (object)[
                'name'       => __('Google reCaptcha'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'google_recaptcha_tab'],
            (object)[
                'name'       => __('Google Tag Manager'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'googel_tag_tab',
            ],
            (object)[
                'name'       => __('Google Analytic'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'google_analytic_tab',
            ],
            (object)[
                'name'       => __('Facebook Pixel'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'facebook_pixel_tab',
            ],
            (object)[
                'name'       => __('Social Login'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'social_login_tab',
            ],
            (object)[
                'name'       => __('Tawk Chat'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'tawk_chat_tab',
            ],
            (object)[
                'name'       => __('Pusher'),
                'route'      => self::url('admin.crediential-setting'),
                'permission' => 'setting.view',
                'tab'        => 'pusher_tab',
            ],
            (object)[
                'name'       => __('Email Configuration'),
                'route'      => self::url('admin.email-configuration'),
                'permission' => 'setting.view',
                'tab'        => 'setting_tab',
            ],
            (object)[
                'name'       => __('Email Template'),
                'route'      => self::url('admin.email-configuration'),
                'permission' => 'setting.view',
                'tab'        => 'email_template_tab',
            ],
            (object)[
                'name'       => __('SEO Setup'),
                'route'      => self::url('admin.seo-setting'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('Custom CSS'),
                'route'      => self::url('admin.custom-code','css'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('Custom JS'),
                'route'      => self::url('admin.custom-code','js'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('Clear cache'),
                'route'      => self::url('admin.cache-clear'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('Database Clear'),
                'route'      => self::url('admin.database-clear'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('System Update'),
                'route'      => self::url('admin.system-update.index'),
                'permission' => 'setting.view',
            ],
            (object) [
                'name' => __('Manage Addons'),
                'route' => self::url('admin.addons.view'),
                'permission' => 'setting.view',
            ],
            (object)[
                'name'       => __('Manage Language'),
                'route'      => self::url('admin.languages.index'),
                'permission' => 'language.view',
            ],
            (object)[
                'name'       => __('Payment Gateway'),
                'route'      => self::url('admin.basicpayment'),
                'permission' => 'basic.payment.view',
            ],
            (object)[
                'name'       => __('Multi Currency'),
                'route'      => self::url('admin.currency.index'),
                'permission' => 'currency.view',
            ],
            (object)[
                'name'       => __('Manage Admin'),
                'route'      => self::url('admin.admin.index'),
                'permission' => 'admin.view',
            ],
            (object)[
                'name'       => __('Role & Permissions'),
                'route'      => self::url('admin.role.index'),
                'permission' => 'role.view',
            ],
        ];
        // Skip pages whose feature is switched off (their routes no longer exist).
        return (object) array_values(array_filter($route_list, fn ($item) => $item->route !== null));
    }

    private static function url(string $name, mixed $parameters = []): ?string
    {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $parameters) : null;
    }
}