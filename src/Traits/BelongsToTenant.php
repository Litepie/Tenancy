<?php

namespace Litepie\Tenancy\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Litepie\Tenancy\Contracts\TenantContract;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant') && app('tenant')) {
                $tenantColumn = (new static)->getTenantColumn();
                $builder->where($tenantColumn, app('tenant')->getTenantId());
            }
        });

        static::creating(function (Model $model) {
            if (app()->bound('tenant') && app('tenant')) {
                $tenantColumn = $model->getTenantColumn();
                if (!$model->{$tenantColumn}) {
                    $model->{$tenantColumn} = app('tenant')->getTenantId();
                }
            }
        });
    }

    /**
     * Get the tenant column name.
     */
    public function getTenantColumn(): string
    {
        return $this->tenantColumn ?? 'tenant_id';
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        $tenantModel = config('tenancy.tenant_model');
        return $this->belongsTo($tenantModel, $this->getTenantColumn());
    }

    /**
     * Scope query to exclude tenant filtering.
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope query to specific tenant.
     */
    public function scopeForTenant(Builder $query, TenantContract $tenant): Builder
    {
        return $query->withoutGlobalScope('tenant')
                    ->where($this->getTenantColumn(), $tenant->getTenantId());
    }

    /**
     * Check if model belongs to current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        if (!app()->bound('tenant') || !app('tenant')) {
            return false;
        }

        return $this->{$this->getTenantColumn()} === app('tenant')->getTenantId();
    }
}
