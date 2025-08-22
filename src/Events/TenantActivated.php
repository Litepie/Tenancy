<?php

namespace Litepie\Tenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Tenancy\Contracts\TenantContract;

class TenantActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TenantContract $tenant,
        public ?TenantContract $previousTenant = null
    ) {}
}
