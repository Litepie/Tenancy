<?php

namespace Litepie\Tenancy\Contracts;

use Illuminate\Http\Request;

interface TenancyManagerContract
{
    /**
     * Initialize the tenancy system.
     */
    public function initialize(): void;

    /**
     * Get the current tenant.
     */
    public function current(): ?TenantContract;

    /**
     * Check if there's a current tenant.
     */
    public function hasTenant(): bool;

    /**
     * Set the current tenant.
     */
    public function setTenant(?TenantContract $tenant): void;

    /**
     * Clear the current tenant.
     */
    public function clearTenant(): void;

    /**
     * Detect tenant from request.
     */
    public function detectTenant(Request $request): ?TenantContract;

    /**
     * Execute callback in tenant context.
     */
    public function executeInTenant(TenantContract $tenant, callable $callback): mixed;

    /**
     * Execute callback in landlord context.
     */
    public function executeInLandlord(callable $callback): mixed;

    /**
     * Get all tenants.
     */
    public function getAllTenants(): iterable;

    /**
     * Find tenant by ID.
     */
    public function findTenant(string|int $id): ?TenantContract;

    /**
     * Create a new tenant.
     */
    public function createTenant(array $attributes): TenantContract;
}
