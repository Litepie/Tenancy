<?php

namespace Litepie\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Litepie\Tenancy\Contracts\TenancyManagerContract;

class InitializeTenancy
{
    public function __construct(
        protected TenancyManagerContract $tenancyManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->tenancyManager->initialize();
        
        return $next($request);
    }
}
