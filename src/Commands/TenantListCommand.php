<?php

namespace Litepie\Tenancy\Commands;

use Illuminate\Console\Command;
use Litepie\Tenancy\Contracts\TenancyManagerContract;

class TenantListCommand extends Command
{
    protected $signature = 'tenant:list {--active : Only show active tenants}';
    protected $description = 'List all tenants';

    public function handle(TenancyManagerContract $tenancyManager): int
    {
        $tenants = $tenancyManager->getAllTenants();
        
        if ($this->option('active')) {
            $tenants = $tenants->where('is_active', true);
        }

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Domain', 'Status', 'Created At'];
        $rows = [];

        foreach ($tenants as $tenant) {
            $rows[] = [
                $tenant->getTenantId(),
                $tenant->getConfig('name', 'N/A'),
                $tenant->getDomain() ?? 'N/A',
                $tenant->isActive() ? 'Active' : 'Inactive',
                $tenant->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
