<?php

namespace Litepie\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Litepie\Tenancy\Exceptions\TenantNotFoundException;

class RequiresTenant
{
    public function __construct(
        protected TenancyManagerContract $tenancyManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->tenancyManager->hasTenant()) {
            throw new TenantNotFoundException('No tenant found for this request.');
        }
        
        return $next($request);
    }
}
