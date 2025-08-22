<?php

namespace Litepie\Tenancy;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Litepie\Tenancy\Commands\TenancyInstallCommand;
use Litepie\Tenancy\Commands\TenancyMigrateCommand;
use Litepie\Tenancy\Commands\TenancyRunCommand;
use Litepie\Tenancy\Commands\TenancySeedCommand;
use Litepie\Tenancy\Commands\TenantCreateCommand;
use Litepie\Tenancy\Commands\TenantListCommand;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Litepie\Tenancy\Contracts\TenantDetectorContract;
use Litepie\Tenancy\Contracts\DatabaseStrategyContract;
use Litepie\Tenancy\Contracts\CacheStrategyContract;
use Litepie\Tenancy\Contracts\StorageStrategyContract;
use Litepie\Tenancy\Managers\TenancyManager;
use Litepie\Tenancy\Detectors\DomainTenantDetector;
use Litepie\Tenancy\Strategies\SeparateDatabaseStrategy;
use Litepie\Tenancy\Strategies\PrefixCacheStrategy;
use Litepie\Tenancy\Strategies\SeparateStorageStrategy;
use Litepie\Tenancy\Middleware\IdentifyTenant;
use Litepie\Tenancy\Middleware\RequireTenant;
use Litepie\Tenancy\Middleware\PreventCrossTenantAccess;

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
        $this->registerEventListeners();
        $this->bootTenancy();
    }

    /**
     * Register contracts and their implementations.
     */
    protected function registerContracts(): void
    {
        $this->app->bind(TenantContract::class, config('tenancy.tenant_model'));
        
        $this->app->singleton(TenancyManagerContract::class, TenancyManager::class);
        
        $this->app->bind(TenantDetectorContract::class, function ($app) {
            $strategy = config('tenancy.detection.strategy');
            $detectorClass = config('tenancy.detection.detector_class');
            
            if ($detectorClass) {
                return $app->make($detectorClass);
            }
            
            return match ($strategy) {
                'domain' => $app->make(DomainTenantDetector::class),
                'subdomain' => $app->make(\Litepie\Tenancy\Detectors\SubdomainTenantDetector::class),
                'header' => $app->make(\Litepie\Tenancy\Detectors\HeaderTenantDetector::class),
                'path' => $app->make(\Litepie\Tenancy\Detectors\PathTenantDetector::class),
                default => $app->make(DomainTenantDetector::class),
            };
        });
        
        $this->app->bind(DatabaseStrategyContract::class, function ($app) {
            $strategy = config('tenancy.database.strategy');
            
            return match ($strategy) {
                'separate' => $app->make(SeparateDatabaseStrategy::class),
                'single' => $app->make(\Litepie\Tenancy\Strategies\SingleDatabaseStrategy::class),
                'hybrid' => $app->make(\Litepie\Tenancy\Strategies\HybridDatabaseStrategy::class),
                default => $app->make(SeparateDatabaseStrategy::class),
            };
        });
        
        $this->app->bind(CacheStrategyContract::class, function ($app) {
            $strategy = config('tenancy.cache.strategy');
            
            return match ($strategy) {
                'prefix' => $app->make(PrefixCacheStrategy::class),
                'separate' => $app->make(\Litepie\Tenancy\Strategies\SeparateCacheStrategy::class),
                'shared' => $app->make(\Litepie\Tenancy\Strategies\SharedCacheStrategy::class),
                default => $app->make(PrefixCacheStrategy::class),
            };
        });
        
        $this->app->bind(StorageStrategyContract::class, function ($app) {
            $strategy = config('tenancy.storage.strategy');
            
            return match ($strategy) {
                'separate' => $app->make(SeparateStorageStrategy::class),
                'shared' => $app->make(\Litepie\Tenancy\Strategies\SharedStorageStrategy::class),
                default => $app->make(SeparateStorageStrategy::class),
            };
        });
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
                TenancyInstallCommand::class,
                TenancyMigrateCommand::class,
                TenancyRunCommand::class,
                TenancySeedCommand::class,
                TenantCreateCommand::class,
                TenantListCommand::class,
            ]);
        }
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('tenant.identify', IdentifyTenant::class);
        $router->aliasMiddleware('tenant.require', RequireTenant::class);
        $router->aliasMiddleware('tenant.prevent_cross_access', PreventCrossTenantAccess::class);
        
        // Auto-register middleware if configured
        if (config('tenancy.middleware.identify_tenant')) {
            $router->pushMiddlewareToGroup('web', IdentifyTenant::class);
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        if (config('tenancy.events.fire_events')) {
            $listeners = config('tenancy.events.listeners', []);
            
            foreach ($listeners as $event => $eventListeners) {
                foreach ((array) $eventListeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    /**
     * Boot the tenancy system.
     */
    protected function bootTenancy(): void
    {
        if (!$this->app->runningInConsole()) {
            $this->app->make(TenancyManagerContract::class)->initialize();
        }
        
        $this->bootQueueTenancy();
    }

    /**
     * Boot queue tenancy features.
     */
    protected function bootQueueTenancy(): void
    {
        if (config('tenancy.queue.tenant_aware_by_default')) {
            Queue::createPayloadUsing(function ($connectionName, $queue, $payload) {
                if (tenancy()->hasTenant()) {
                    $payload[config('tenancy.queue.tenant_parameter')] = tenancy()->current()->getKey();
                }
                
                return $payload;
            });
        }
    }
}
