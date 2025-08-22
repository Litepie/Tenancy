<?php

namespace Litepie\Tenancy\Contracts;

interface DatabaseStrategyContract
{
    /**
     * Switch to the tenant's database.
     */
    public function switchToTenant(TenantContract $tenant): void;

    /**
     * Switch to the landlord database.
     */
    public function switchToLandlord(): void;

    /**
     * Get the current database connection name.
     */
    public function getCurrentConnection(): string;

    /**
     * Create tenant database if it doesn't exist.
     */
    public function createTenantDatabase(TenantContract $tenant): bool;

    /**
     * Drop tenant database.
     */
    public function dropTenantDatabase(TenantContract $tenant): bool;

    /**
     * Check if tenant database exists.
     */
    public function tenantDatabaseExists(TenantContract $tenant): bool;

    /**
     * Migrate tenant database.
     */
    public function migrateTenantDatabase(TenantContract $tenant, array $options = []): void;

    /**
     * Seed tenant database.
     */
    public function seedTenantDatabase(TenantContract $tenant, array $options = []): void;
}
