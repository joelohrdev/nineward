<?php

namespace App\Providers;

use App\Contracts\RegistrationPaymentGateway;
use App\Models\Organization;
use App\Payments\StripeRegistrationPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            RegistrationPaymentGateway::class,
            fn (): StripeRegistrationPaymentGateway => new StripeRegistrationPaymentGateway(config('cashier.secret')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Cashier::useCustomerModel(Organization::class);

        $this->configureRateLimiting();
    }

    /**
     * Public registration is anonymous, so it's throttled per visitor and form,
     * with a daily cap per visitor across all forms.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('registrations', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip().'|'.$request->route('organization').'|'.$request->route('form')),
            Limit::perDay(50)->by($request->ip()),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
