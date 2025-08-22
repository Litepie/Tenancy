<?php

namespace Litepie\Tenancy\Contracts;

interface CacheStrategyContract
{
    /**
     * Apply tenant-specific cache configuration.
     */
    public function applyTenantCache(TenantContract $tenant): void;

    /**
     * Remove tenant-specific cache configuration.
     */
    public function removeTenantCache(): void;

    /**
     * Clear tenant cache.
     */
    public function clearTenantCache(TenantContract $tenant): void;

    /**
     * Get tenant cache key prefix.
     */
    public function getTenantCachePrefix(TenantContract $tenant): string;
}
