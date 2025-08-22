<?php

namespace Litepie\Tenancy\Detectors;

use Illuminate\Http\Request;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\TenantDetectorContract;

class DomainDetector implements TenantDetectorContract
{
    /**
     * Detect tenant from request.
     */
    public function detect(Request $request): ?TenantContract
    {
        $host = $request->getHost();
        
        if (empty($host)) {
            return null;
        }

        $tenantModel = config('tenancy.tenant_model');
        
        return $tenantModel::where('domain', $host)
            ->active()
            ->first();
    }

    /**
     * Check if this detector can detect from the given request.
     */
    public function canDetect(Request $request): bool
    {
        return !empty($request->getHost());
    }

    /**
     * Get detection priority.
     */
    public function priority(): int
    {
        return 20;
    }
}
