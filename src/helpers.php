<?php

if (!function_exists('tenancy')) {
    /**
     * Get the tenancy manager instance.
     */
    function tenancy(): \Litepie\Tenancy\Contracts\TenancyManagerContract
    {
        return app(\Litepie\Tenancy\Contracts\TenancyManagerContract::class);
    }
}

if (!function_exists('current_tenant')) {
    /**
     * Get the current tenant.
     */
    function current_tenant(): ?\Litepie\Tenancy\Contracts\TenantContract
    {
        return tenancy()->current();
    }
}

if (!function_exists('tenant_config')) {
    /**
     * Get tenant configuration value.
     */
    function tenant_config(string $key, mixed $default = null): mixed
    {
        $tenant = current_tenant();
        
        if (!$tenant) {
            return $default;
        }
        
        return $tenant->getConfig($key, $default);
    }
}

if (!function_exists('is_tenant_context')) {
    /**
     * Check if we are currently in a tenant context.
     */
    function is_tenant_context(): bool
    {
        return tenancy()->hasTenant();
    }
}

if (!function_exists('execute_in_tenant')) {
    /**
     * Execute callback in tenant context.
     */
    function execute_in_tenant(\Litepie\Tenancy\Contracts\TenantContract $tenant, callable $callback): mixed
    {
        return tenancy()->executeInTenant($tenant, $callback);
    }
}

if (!function_exists('execute_in_landlord')) {
    /**
     * Execute callback in landlord context.
     */
    function execute_in_landlord(callable $callback): mixed
    {
        return tenancy()->executeInLandlord($callback);
    }
}
