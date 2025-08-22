<?php

namespace Litepie\Tenancy\Contracts;

interface StorageStrategyContract
{
    /**
     * Apply tenant-specific storage configuration.
     */
    public function applyTenantStorage(TenantContract $tenant): void;

    /**
     * Remove tenant-specific storage configuration.
     */
    public function removeTenantStorage(): void;

    /**
     * Get tenant storage disk name.
     */
    public function getTenantDiskName(TenantContract $tenant): string;

    /**
     * Create tenant storage disk.
     */
    public function createTenantDisk(TenantContract $tenant): void;

    /**
     * Remove tenant storage disk.
     */
    public function removeTenantDisk(TenantContract $tenant): void;
}
