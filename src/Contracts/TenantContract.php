<?php

namespace Litepie\Tenancy\Contracts;

interface TenantContract
{
    /**
     * Get the tenant's unique identifier.
     */
    public function getTenantId(): string|int;

    /**
     * Get the tenant's database name.
     */
    public function getDatabaseName(): ?string;

    /**
     * Get the tenant's domain.
     */
    public function getDomain(): ?string;

    /**
     * Activate this tenant.
     */
    public function activate(): void;

    /**
     * Check if this tenant is currently active.
     */
    public function isActive(): bool;

    /**
     * Execute a callback within this tenant's context.
     */
    public function execute(callable $callback): mixed;

    /**
     * Get tenant-specific configuration.
     */
    public function getConfig(string $key = null, mixed $default = null): mixed;

    /**
     * Set tenant-specific configuration.
     */
    public function setConfig(string $key, mixed $value): void;
}
