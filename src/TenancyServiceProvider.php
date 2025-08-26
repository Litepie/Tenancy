<?php

namespace Litepie\Tenancy;

use Illuminate\Support\ServiceProvider;
use Litepie\Tenancy\Commands\TenantDiagnoseCommand;
use Litepie\Tenancy\Commands\TenantListCommand;
use Litepie\Tenancy\Commands\TenantMigrateCommand;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Litepie\Tenancy\Contracts\TenantDetectorContract;
use Litepie\Tenancy\Contracts\DatabaseStrategyContract;
use Litepie\Tenancy\Contracts\CacheStrategyContract;
use Litepie\Tenancy\Contracts\StorageStrategyContract;
use Litepie\Tenancy\Managers\TenancyManager;
use Litepie\Tenancy\Detectors\DomainDetector;
use Litepie\Tenancy\Detectors\SubdomainDetector;
use Litepie\Tenancy\Strategies\Database\SeparateDatabaseStrategy;
use Litepie\Tenancy\Strategies\Cache\PrefixedCacheStrategy;
use Litepie\Tenancy\Strategies\Storage\TenantPathStrategy;
use Litepie\Tenancy\Middleware\InitializeTenancy;
use Litepie\Tenancy\Middleware\RequiresTenant;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tenancy.php', 'tenancy');

        $this->registerContracts();
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->bootTenancy();
    }

    /**
     * Register contracts and their implementations.
     */
    protected function registerContracts(): void
    {
        $this->app->bind(TenantContract::class, config('tenancy.tenant_model', \Litepie\Tenancy\Models\Tenant::class));
        
        $this->app->singleton(TenancyManagerContract::class, TenancyManager::class);
        
        $this->app->bind(TenantDetectorContract::class, function ($app) {
            $strategy = config('tenancy.detection.strategy', 'domain');
            
            return match ($strategy) {
                'domain' => $app->make(DomainDetector::class),
                'subdomain' => $app->make(SubdomainDetector::class),
                default => $app->make(DomainDetector::class),
            };
        });
        
        $this->app->bind(DatabaseStrategyContract::class, SeparateDatabaseStrategy::class);
        $this->app->bind(CacheStrategyContract::class, PrefixedCacheStrategy::class);
        $this->app->bind(StorageStrategyContract::class, TenantPathStrategy::class);
    }

    /**
     * Register core services.
     */
    protected function registerServices(): void
    {
        $this->app->singleton('tenancy', function ($app) {
            return $app->make(TenancyManagerContract::class);
        });
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'tenancy-migrations');
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantDiagnoseCommand::class,
                TenantListCommand::class,
                TenantMigrateCommand::class,
            ]);
        }
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('tenant.initialize', InitializeTenancy::class);
        $router->aliasMiddleware('tenant.require', RequiresTenant::class);
    }

    /**
     * Boot the tenancy system.
     */
    protected function bootTenancy(): void
    {
        if (!$this->app->runningInConsole()) {
            // Initialize tenancy if needed
        }
    }
}
