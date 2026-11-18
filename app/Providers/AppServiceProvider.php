<?php

namespace App\Providers;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\GlobalSetting\app\Models\CustomPagination;
use Modules\GlobalSetting\app\Models\Setting;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        // Lets the Anthropic SDK (Tafuta) run on hosts that disable php_uname().
        require_once app_path('Support/AnthropicHostCompat.php');
        // Per-request memo of verified providers for the public badge (reset for every request/job).
        $this->app->scoped(\App\Services\LicenceVerification::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        \App\Models\ProviderPaymentTransaction::observe(\App\Observers\ProviderPaymentObserver::class);
        Relation::morphMap([
            'doctor' => \Modules\Doctor\app\Models\Doctor::class,
            'clinic' => \Modules\Doctor\app\Models\Clinic::class,
            'school' => \Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class,
            'supplier' => \Modules\MedicalSupplier\app\Models\MedicalSupplier::class,
        ]);
        foreach ([\Modules\Doctor\app\Models\Doctor::class, \Modules\Doctor\app\Models\Clinic::class, \Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class, \Modules\MedicalSupplier\app\Models\MedicalSupplier::class] as $clientModel) {
            $clientModel::observe(\App\Observers\ClientCreationObserver::class);
        }

        try {
            $setting = Cache::rememberForever('setting', function () {
                $setting_info = Setting::get();
                $setting = [];
                foreach ($setting_info as $setting_item) {
                    $setting[$setting_item->key] = $setting_item->value;
                }
                $setting = (object) $setting;

                return $setting;
            });

            // Setup mail configuration.
            // If the DB rows still hold the factory placeholder values (i.e. the admin
            // has not yet configured SMTP through the setup wizard or admin panel),
            // fall back to whatever is set in the .env file so mail always works.
            $dbPlaceholders = ['mail_username', 'mail_password', 'sandbox.smtp.mailtrap.io'];

            $dbHost       = $setting->mail_host ?? null;
            $dbUsername   = $setting->mail_username ?? null;
            $dbPassword   = $setting->mail_password ?? null;
            $dbPort       = $setting->mail_port ?? null;
            $dbEncryption = $setting->mail_encryption ?? null;
            $dbFromEmail  = $setting->mail_sender_email ?? null;
            $dbFromName   = $setting->mail_sender_name ?? null;

            $useEnv = in_array($dbHost, $dbPlaceholders)
                   || in_array($dbUsername, $dbPlaceholders)
                   || in_array($dbPassword, $dbPlaceholders)
                   || empty($dbHost)
                   || empty($dbUsername);

            if ($useEnv) {
                // DB not configured yet — trust .env
                $mailHost       = config('mail.mailers.smtp.host');
                $mailPort       = config('mail.mailers.smtp.port', 587);
                $mailEncryption = config('mail.mailers.smtp.encryption');
                $mailUsername   = config('mail.mailers.smtp.username');
                $mailPassword   = config('mail.mailers.smtp.password');
                $mailFromAddress = config('mail.from.address');
                $mailFromName   = config('mail.from.name', config('app.name'));
            } else {
                // DB has real values — use them
                $mailHost        = $dbHost;
                $mailPort        = $dbPort;
                $mailEncryption  = $dbEncryption;
                $mailUsername    = $dbUsername;
                $mailPassword    = $dbPassword;
                $mailFromAddress = $dbFromEmail;
                $mailFromName    = $dbFromName;
            }

            config(['mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                'transport'  => 'smtp',
                'host'       => $mailHost,
                'port'       => $mailPort,
                'encryption' => $mailEncryption,
                'username'   => $mailUsername,
                'password'   => $mailPassword,
                'timeout'    => 30,
            ], $useEnv ? [] : ['url' => null])]);
            config(['mail.from.address' => $mailFromAddress]);
            config(['mail.from.name'    => $mailFromName]);

            // setup timezone globally
            config(['app.timezone' => $setting->timezone ?? config('app.timezone')]);

            $pusherCluster = $setting->pusher_app_cluster ?? config('broadcasting.connections.pusher.options.cluster');
            config(['broadcasting.connections.pusher.key' => $setting->pusher_app_key ?? config('broadcasting.connections.pusher.key')]);
            config(['broadcasting.connections.pusher.secret' => $setting->pusher_app_secret ?? config('broadcasting.connections.pusher.secret')]);
            config(['broadcasting.connections.pusher.app_id' => $setting->pusher_app_id ?? config('broadcasting.connections.pusher.app_id')]);
            config(['broadcasting.connections.pusher.options.cluster' => $pusherCluster]);
            if ($pusherCluster) {
                config(['broadcasting.connections.pusher.options.host' => 'api-' . $pusherCluster . '.pusher.com']);
            }

            Cache::rememberForever('CustomPagination', function () {
                $custom_pagination = CustomPagination::all();
                $pagination = [];
                foreach ($custom_pagination as $item) {
                    $pagination[str_replace(' ', '_', strtolower($item->section_name))] = $item->item_qty;
                }
                $pagination = (object) $pagination;

                return $pagination;
            });
        } catch (Exception $ex) {
            $setting = null;
            Log::error($ex->getMessage());
        }

        try {
            $contactInfo = contactInfo();
        } catch (Exception $e) {
            $setting = (object) ['timezone' => config('app.timezone')];
            $contactInfo = null;
        }
        // setup timezone globally
        date_default_timezone_set($setting?->timezone ?? config('app.timezone'));

        View::composer('*', function ($view) use ($contactInfo) {
            $setting = Cache::get('setting');
            $view->with('setting', $setting);
            $view->with('contactInfo', $contactInfo);
        });

        /**
         * Register custom blade directives
         * this can be used for permission or permissions check
         * this check will be perform on admin guard
         */
        $this->registerBladeDirectives();
        Paginator::useBootstrapFour();
    }

    protected function registerBladeDirectives() {
        Blade::directive('adminCan', function ($permission) {
            return "<?php if(auth()->guard('admin')->user()->can({$permission})): ?>";
        });

        Blade::directive('endadminCan', function () {
            return '<?php endif; ?>';
        });
    }
}
