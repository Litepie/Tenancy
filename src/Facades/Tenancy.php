<?php

namespace Litepie\Tenancy\Facades;

use Illuminate\Support\Facades\Facade;
use Litepie\Tenancy\Contracts\TenancyManagerContract;

/**
 * @method static \Litepie\Tenancy\Contracts\TenantContract|null current()
 * @method static bool hasTenant()
 * @method static void setTenant(\Litepie\Tenancy\Contracts\TenantContract|null $tenant)
 * @method static void clearTenant()
 * @method static \Litepie\Tenancy\Contracts\TenantContract|null detectTenant(\Illuminate\Http\Request $request)
 * @method static mixed executeInTenant(\Litepie\Tenancy\Contracts\TenantContract $tenant, callable $callback)
 * @method static mixed executeInLandlord(callable $callback)
 * @method static iterable getAllTenants()
 * @method static \Litepie\Tenancy\Contracts\TenantContract|null findTenant(string|int $id)
 * @method static \Litepie\Tenancy\Contracts\TenantContract createTenant(array $attributes)
 * @method static void initialize()
 *
 * @see \Litepie\Tenancy\Managers\TenancyManager
 */
class Tenancy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TenancyManagerContract::class;
    }
}
