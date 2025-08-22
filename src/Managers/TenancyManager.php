<?php

namespace Litepie\Tenancy\Managers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Litepie\Tenancy\Contracts\TenantDetectorContract;
use Litepie\Tenancy\Contracts\DatabaseStrategyContract;
use Litepie\Tenancy\Contracts\CacheStrategyContract;
use Litepie\Tenancy\Contracts\StorageStrategyContract;
use Litepie\Tenancy\Events\TenantActivated;
use Litepie\Tenancy\Events\TenantDeactivated;
use Litepie\Tenancy\Exceptions\TenantNotFoundException;

class TenancyManager implements TenancyManagerContract
{
    protected ?TenantContract $currentTenant = null;
    protected ?TenantContract $previousTenant = null;

    public function __construct(
        protected TenantDetectorContract $detector,
        protected DatabaseStrategyContract $databaseStrategy,
        protected CacheStrategyContract $cacheStrategy,
        protected StorageStrategyContract $storageStrategy
    ) {}

    /**
     * Initialize the tenancy system.
     */
    public function initialize(): void
    {
        if (!app()->runningInConsole()) {
            $request = request();
            $tenant = $this->detectTenant($request);
            
            if ($tenant) {
                $this->setTenant($tenant);
            }
        }
    }

    /**
     * Get the current tenant.
     */
    public function current(): ?TenantContract
    {
        return $this->currentTenant;
    }

    /**
     * Check if there's a current tenant.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Set the current tenant.
     */
    public function setTenant(?TenantContract $tenant): void
    {
        $this->previousTenant = $this->currentTenant;

        if ($tenant === null) {
            $this->clearTenant();
            return;
        }

        $this->currentTenant = $tenant;
        $this->applyTenantConfiguration($tenant);

        if (config('tenancy.debug.log_tenant_switches')) {
            Log::info('Tenant activated', [
                'tenant_id' => $tenant->getTenantId(),
                'tenant_name' => $tenant->getConfig('name'),
            ]);
        }

        event(new TenantActivated($tenant, $this->previousTenant));
    }

    /**
     * Clear the current tenant.
     */
    public function clearTenant(): void
    {
        $previousTenant = $this->currentTenant;
        $this->currentTenant = null;

        $this->removeTenantConfiguration();

        if (config('tenancy.debug.log_tenant_switches')) {
            Log::info('Tenant deactivated', [
                'previous_tenant_id' => $previousTenant?->getTenantId(),
            ]);
        }

        event(new TenantDeactivated($previousTenant));
    }

    /**
     * Detect tenant from request.
     */
    public function detectTenant(Request $request): ?TenantContract
    {
        if (!$this->detector->canDetect($request)) {
            return null;
        }

        $cacheKey = $this->getTenantCacheKey($request);
        
        if (config('tenancy.performance.cache_tenant_lookup')) {
            $tenant = Cache::remember($cacheKey, config('tenancy.detection.cache_ttl', 3600), function () use ($request) {
                return $this->detector->detect($request);
            });
        } else {
            $tenant = $this->detector->detect($request);
        }

        return $tenant;
    }

    /**
     * Execute callback in tenant context.
     */
    public function executeInTenant(TenantContract $tenant, callable $callback): mixed
    {
        $originalTenant = $this->currentTenant;

        try {
            $this->setTenant($tenant);
            return $callback();
        } finally {
            if ($originalTenant) {
                $this->setTenant($originalTenant);
            } else {
                $this->clearTenant();
            }
        }
    }

    /**
     * Execute callback in landlord context.
     */
    public function executeInLandlord(callable $callback): mixed
    {
        $originalTenant = $this->currentTenant;

        try {
            $this->clearTenant();
            return $callback();
        } finally {
            if ($originalTenant) {
                $this->setTenant($originalTenant);
            }
        }
    }

    /**
     * Get all tenants.
     */
    public function getAllTenants(): iterable
    {
        $tenantModel = config('tenancy.tenant_model');
        return $tenantModel::active()->get();
    }

    /**
     * Find tenant by ID.
     */
    public function findTenant(string|int $id): ?TenantContract
    {
        $tenantModel = config('tenancy.tenant_model');
        return $tenantModel::find($id);
    }

    /**
     * Create a new tenant.
     */
    public function createTenant(array $attributes): TenantContract
    {
        $tenantModel = config('tenancy.tenant_model');
        return $tenantModel::create($attributes);
    }

    /**
     * Apply tenant-specific configuration.
     */
    protected function applyTenantConfiguration(TenantContract $tenant): void
    {
        // Apply database strategy
        $this->databaseStrategy->switchToTenant($tenant);

        // Apply cache strategy
        $this->cacheStrategy->applyTenantCache($tenant);

        // Apply storage strategy
        $this->storageStrategy->applyTenantStorage($tenant);

        // Bind tenant in container
        app()->instance('tenant', $tenant);
        app()->instance(TenantContract::class, $tenant);
    }

    /**
     * Remove tenant-specific configuration.
     */
    protected function removeTenantConfiguration(): void
    {
        // Switch to landlord database
        $this->databaseStrategy->switchToLandlord();

        // Remove tenant cache
        $this->cacheStrategy->removeTenantCache();

        // Remove tenant storage
        $this->storageStrategy->removeTenantStorage();

        // Remove tenant from container
        app()->forgetInstance('tenant');
        app()->forgetInstance(TenantContract::class);
    }

    /**
     * Get cache key for tenant lookup.
     */
    protected function getTenantCacheKey(Request $request): string
    {
        $identifier = $this->getTenantIdentifier($request);
        return config('tenancy.detection.cache_key') . ':' . md5($identifier);
    }

    /**
     * Get tenant identifier from request.
     */
    protected function getTenantIdentifier(Request $request): string
    {
        $strategy = config('tenancy.detection.strategy');
        
        return match ($strategy) {
            'domain' => $request->getHost(),
            'subdomain' => explode('.', $request->getHost())[0] ?? '',
            'header' => $request->header(config('tenancy.detection.header', 'X-Tenant-ID')) ?? '',
            'path' => $request->segment(1) ?? '',
            default => $request->getHost(),
        };
    }
}
