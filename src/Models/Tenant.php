<?php

namespace Litepie\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Database\Factories\TenantFactory;

class Tenant extends Model implements TenantContract
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'domain',
        'subdomain',
        'database',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'settings' => '{}',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TenantFactory::new();
    }

    /**
     * Get the tenant's unique identifier.
     */
    public function getTenantId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Get the tenant's database name.
     */
    public function getDatabaseName(): ?string
    {
        return $this->database;
    }

    /**
     * Get the tenant's domain.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Activate this tenant.
     */
    public function activate(): void
    {
        tenancy()->setTenant($this);
    }

    /**
     * Check if this tenant is currently active.
     */
    public function isActive(): bool
    {
        return tenancy()->current()?->getTenantId() === $this->getTenantId();
    }

    /**
     * Execute a callback within this tenant's context.
     */
    public function execute(callable $callback): mixed
    {
        return tenancy()->executeInTenant($this, $callback);
    }

    /**
     * Get tenant-specific configuration.
     */
    public function getConfig(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->settings ?? [];
        }

        return data_get($this->settings, $key, $default);
    }

    /**
     * Set tenant-specific configuration.
     */
    public function setConfig(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Scope to only active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by domain.
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope to find by subdomain.
     */
    public function scopeBySubdomain($query, string $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'domain';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            // Generate database name if not provided
            if (empty($tenant->database)) {
                $tenant->database = 'tenant_' . str_replace('-', '_', \Illuminate\Support\Str::slug($tenant->name));
            }
        });

        static::created(function ($tenant) {
            // Create tenant database and run migrations if auto_create is enabled
            if (config('tenancy.database.auto_create')) {
                $databaseStrategy = app(\Litepie\Tenancy\Contracts\DatabaseStrategyContract::class);
                $databaseStrategy->createTenantDatabase($tenant);
                
                if (config('tenancy.database.auto_migrate')) {
                    $databaseStrategy->migrateTenantDatabase($tenant);
                }
            }
        });

        static::deleting(function ($tenant) {
            // Clean up tenant resources when deleting
            if ($tenant->isForceDeleting()) {
                $databaseStrategy = app(\Litepie\Tenancy\Contracts\DatabaseStrategyContract::class);
                $databaseStrategy->dropTenantDatabase($tenant);
            }
        });
    }
}
