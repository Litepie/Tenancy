<?php

namespace Litepie\Tenancy\Contracts;

use Illuminate\Http\Request;

interface TenantDetectorContract
{
    /**
     * Detect the current tenant from the request.
     */
    public function detect(Request $request): ?TenantContract;

    /**
     * Check if the detector can handle the request.
     */
    public function canDetect(Request $request): bool;

    /**
     * Get the priority of this detector.
     */
    public function priority(): int;
}
