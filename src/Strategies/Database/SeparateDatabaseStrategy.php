<?php

namespace Litepie\Tenancy\Strategies\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Litepie\Tenancy\Contracts\TenantContract;
use Litepie\Tenancy\Contracts\DatabaseStrategyContract;

class SeparateDatabaseStrategy implements DatabaseStrategyContract
{
    public function __construct(
        protected DatabaseManager $databaseManager
    ) {}

    /**
     * Switch to tenant database.
     */
    public function switchToTenant(TenantContract $tenant): void
    {
        $connectionName = $this->getTenantConnectionName($tenant);
        $config = $this->getTenantDatabaseConfig($tenant);

        Config::set("database.connections.{$connectionName}", $config);
        
        DB::purge($connectionName);
        DB::setDefaultConnection($connectionName);
    }

    /**
     * Switch to landlord database.
     */
    public function switchToLandlord(): void
    {
        $landlordConnection = config('tenancy.database.landlord_connection', 'mysql');
        DB::setDefaultConnection($landlordConnection);
    }

    /**
     * Create tenant database.
     */
    public function createTenantDatabase(TenantContract $tenant): bool
    {
        $databaseName = $this->getTenantDatabaseName($tenant);
        $landlordConnection = config('tenancy.database.landlord_connection', 'mysql');

        try {
            DB::connection($landlordConnection)
                ->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop tenant database.
     */
    public function dropTenantDatabase(TenantContract $tenant): bool
    {
        $databaseName = $this->getTenantDatabaseName($tenant);
        $landlordConnection = config('tenancy.database.landlord_connection', 'mysql');

        try {
            DB::connection($landlordConnection)
                ->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if tenant database exists.
     */
    public function tenantDatabaseExists(TenantContract $tenant): bool
    {
        $databaseName = $this->getTenantDatabaseName($tenant);
        $landlordConnection = config('tenancy.database.landlord_connection', 'mysql');

        try {
            $databases = DB::connection($landlordConnection)
                ->select("SHOW DATABASES LIKE '{$databaseName}'");
            
            return count($databases) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Migrate tenant database.
     */
    public function migrateTenantDatabase(TenantContract $tenant, array $options = []): void
    {
        $connectionName = $this->getTenantConnectionName($tenant);
        
        $this->switchToTenant($tenant);
        
        $defaultOptions = [
            '--database' => $connectionName,
            '--path' => config('tenancy.database.migrations_path', 'database/migrations/tenant'),
            '--force' => true,
        ];

        \Artisan::call('migrate', array_merge($defaultOptions, $options));
    }

    /**
     * Seed tenant database.
     */
    public function seedTenantDatabase(TenantContract $tenant, array $options = []): void
    {
        $connectionName = $this->getTenantConnectionName($tenant);
        
        $this->switchToTenant($tenant);
        
        $defaultOptions = [
            '--database' => $connectionName,
            '--class' => config('tenancy.database.tenant_seeder', 'TenantSeeder'),
            '--force' => true,
        ];

        \Artisan::call('db:seed', array_merge($defaultOptions, $options));
    }

    /**
     * Get current database connection name.
     */
    public function getCurrentConnection(): string
    {
        return config('database.default');
    }

    /**
     * Get tenant connection name.
     */
    protected function getTenantConnectionName(TenantContract $tenant): string
    {
        return 'tenant_' . $tenant->getTenantId();
    }

    /**
     * Get tenant database name.
     */
    protected function getTenantDatabaseName(TenantContract $tenant): string
    {
        $prefix = config('tenancy.database.tenant_database_prefix', 'tenant_');
        return $prefix . $tenant->getTenantId();
    }

    /**
     * Get tenant database configuration.
     */
    protected function getTenantDatabaseConfig(TenantContract $tenant): array
    {
        $baseConfig = config('database.connections.' . config('tenancy.database.landlord_connection', 'mysql'));
        $databaseName = $this->getTenantDatabaseName($tenant);

        $tenantConfig = $baseConfig;
        $tenantConfig['database'] = $databaseName;

        // Allow tenant-specific database overrides
        if ($tenant->hasConfig('database')) {
            $tenantConfig = array_merge($tenantConfig, $tenant->getConfig('database'));
        }

        return $tenantConfig;
    }
}
