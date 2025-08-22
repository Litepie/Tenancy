<?php

namespace Litepie\Tenancy\Detectors;

use Illuminate\Http\Request;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\TenantDetectorContract;

class SubdomainDetector implements TenantDetectorContract
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

        $hostParts = explode('.', $host);
        
        if (count($hostParts) < 2) {
            return null;
        }

        $subdomain = $hostParts[0];
        
        // Skip www and common subdomains
        if (in_array($subdomain, config('tenancy.detection.excluded_subdomains', ['www', 'mail', 'ftp']))) {
            return null;
        }

        $tenantModel = config('tenancy.tenant_model');
        
        return $tenantModel::where('subdomain', $subdomain)
            ->active()
            ->first();
    }

    /**
     * Check if this detector can detect from the given request.
     */
    public function canDetect(Request $request): bool
    {
        $host = $request->getHost();
        $hostParts = explode('.', $host);
        
        return count($hostParts) >= 2 && 
               !in_array($hostParts[0], config('tenancy.detection.excluded_subdomains', ['www', 'mail', 'ftp']));
    }

    /**
     * Get detection priority.
     */
    public function priority(): int
    {
        return 10;
    }
}
