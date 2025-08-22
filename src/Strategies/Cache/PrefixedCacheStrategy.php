<?php

namespace Litepie\Tenancy\Strategies\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\CacheStrategyContract;

class PrefixedCacheStrategy implements CacheStrategyContract
{
    protected ?string $originalPrefix = null;

    /**
     * Apply tenant-specific cache configuration.
     */
    public function applyTenantCache(TenantContract $tenant): void
    {
        $this->originalPrefix = config('cache.prefix');
        $tenantPrefix = $this->getTenantCachePrefix($tenant);
        
        Config::set('cache.prefix', $tenantPrefix);
        
        // Clear cache manager to force recreation with new prefix
        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');
    }

    /**
     * Remove tenant-specific cache configuration.
     */
    public function removeTenantCache(): void
    {
        if ($this->originalPrefix !== null) {
            Config::set('cache.prefix', $this->originalPrefix);
            $this->originalPrefix = null;
        }
        
        // Clear cache manager to force recreation with original prefix
        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');
    }

    /**
     * Clear all cache for tenant.
     */
    public function clearTenantCache(TenantContract $tenant): void
    {
        $originalPrefix = config('cache.prefix');
        $tenantPrefix = $this->getTenantCachePrefix($tenant);
        
        Config::set('cache.prefix', $tenantPrefix);
        
        try {
            Cache::flush();
        } finally {
            Config::set('cache.prefix', $originalPrefix);
        }
    }

    /**
     * Get cache key for tenant.
     */
    public function getTenantCacheKey(TenantContract $tenant, string $key): string
    {
        $prefix = $this->getTenantCachePrefix($tenant);
        return $prefix . ':' . $key;
    }

    /**
     * Get tenant cache prefix.
     */
    protected function getTenantCachePrefix(TenantContract $tenant): string
    {
        $basePrefix = config('tenancy.cache.tenant_prefix', 'tenant');
        return $basePrefix . '_' . $tenant->getTenantId();
    }
}
