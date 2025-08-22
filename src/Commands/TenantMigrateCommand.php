<?php

namespace Litepie\Tenancy\Commands;

use Illuminate\Console\Command;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Litepie\Tenancy\Contracts\DatabaseStrategyContract;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate 
                           {tenant? : Tenant ID to migrate}
                           {--all : Migrate all tenants}
                           {--fresh : Drop all tables and re-run all migrations}
                           {--seed : Seed the database after migrating}';
    
    protected $description = 'Run migrations for tenant(s)';

    public function handle(
        TenancyManagerContract $tenancyManager,
        DatabaseStrategyContract $databaseStrategy
    ): int {
        $tenantId = $this->argument('tenant');
        $all = $this->option('all');

        if (!$tenantId && !$all) {
            $this->error('Please specify a tenant ID or use --all flag.');
            return self::FAILURE;
        }

        if ($all) {
            return $this->migrateAllTenants($tenancyManager, $databaseStrategy);
        }

        return $this->migrateTenant($tenancyManager, $databaseStrategy, $tenantId);
    }

    protected function migrateAllTenants(
        TenancyManagerContract $tenancyManager,
        DatabaseStrategyContract $databaseStrategy
    ): int {
        $tenants = $tenancyManager->getAllTenants();
        $this->info("Found {$tenants->count()} tenants to migrate.");

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->getTenantId()}");
            
            try {
                $tenancyManager->executeInTenant($tenant, function () use ($databaseStrategy, $tenant) {
                    $options = [];
                    
                    if ($this->option('fresh')) {
                        $options['--fresh'] = true;
                    }
                    
                    $databaseStrategy->migrateTenantDatabase($tenant, $options);
                    
                    if ($this->option('seed')) {
                        $databaseStrategy->seedTenantDatabase($tenant);
                    }
                });
                
                $this->info("✓ Successfully migrated tenant: {$tenant->getTenantId()}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to migrate tenant: {$tenant->getTenantId()} - {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    protected function migrateTenant(
        TenancyManagerContract $tenancyManager,
        DatabaseStrategyContract $databaseStrategy,
        string $tenantId
    ): int {
        $tenant = $tenancyManager->findTenant($tenantId);
        
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantId}");
            return self::FAILURE;
        }

        $this->info("Migrating tenant: {$tenant->getTenantId()}");

        try {
            $tenancyManager->executeInTenant($tenant, function () use ($databaseStrategy, $tenant) {
                $options = [];
                
                if ($this->option('fresh')) {
                    $options['--fresh'] = true;
                }
                
                $databaseStrategy->migrateTenantDatabase($tenant, $options);
                
                if ($this->option('seed')) {
                    $databaseStrategy->seedTenantDatabase($tenant);
                }
            });
            
            $this->info("✓ Successfully migrated tenant: {$tenant->getTenantId()}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to migrate tenant: {$tenant->getTenantId()} - {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
