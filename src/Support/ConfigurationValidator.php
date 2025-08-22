<?php

namespace Litepie\Tenancy\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ConfigurationValidator
{
    /**
     * Validate the tenancy configuration.
     */
    public static function validate(): array
    {
        $errors = [];

        // Validate tenant model
        $tenantModel = config('tenancy.tenant_model');
        if (!class_exists($tenantModel)) {
            $errors[] = "Tenant model class '{$tenantModel}' does not exist.";
        }

        // Validate database strategy
        $databaseStrategy = config('tenancy.database.strategy');
        if (!in_array($databaseStrategy, ['separate', 'single'])) {
            $errors[] = "Invalid database strategy '{$databaseStrategy}'. Must be 'separate' or 'single'.";
        }

        // Validate detection strategy
        $detectionStrategy = config('tenancy.detection.strategy');
        if (!in_array($detectionStrategy, ['domain', 'subdomain', 'header', 'path'])) {
            $errors[] = "Invalid detection strategy '{$detectionStrategy}'.";
        }

        // Validate database connections
        if ($databaseStrategy === 'separate') {
            $landlordConnection = config('tenancy.database.landlord_connection');
            $connections = config('database.connections');
            
            if (!isset($connections[$landlordConnection])) {
                $errors[] = "Landlord database connection '{$landlordConnection}' is not configured.";
            }
        }

        // Validate tenants table exists
        try {
            if (!Schema::hasTable('tenants')) {
                $errors[] = "Tenants table does not exist. Run 'php artisan migrate' to create it.";
            }
        } catch (\Exception $e) {
            $errors[] = "Cannot check tenants table: " . $e->getMessage();
        }

        // Validate cache configuration
        $cacheStrategy = config('tenancy.cache.strategy');
        if (!in_array($cacheStrategy, ['prefixed', 'separate', 'shared'])) {
            $errors[] = "Invalid cache strategy '{$cacheStrategy}'.";
        }

        // Validate storage configuration
        $storageStrategy = config('tenancy.storage.strategy');
        if (!in_array($storageStrategy, ['tenant_path', 'separate_disk', 'shared'])) {
            $errors[] = "Invalid storage strategy '{$storageStrategy}'.";
        }

        return $errors;
    }

    /**
     * Validate tenant database configuration.
     */
    public static function validateTenantDatabase(string $tenantId): array
    {
        $errors = [];
        $strategy = config('tenancy.database.strategy');

        if ($strategy === 'separate') {
            $databaseName = config('tenancy.database.tenant_database_prefix', 'tenant_') . $tenantId;
            $landlordConnection = config('tenancy.database.landlord_connection', 'mysql');

            try {
                $databases = DB::connection($landlordConnection)
                    ->select("SHOW DATABASES LIKE '{$databaseName}'");
                
                if (empty($databases)) {
                    $errors[] = "Tenant database '{$databaseName}' does not exist.";
                }
            } catch (\Exception $e) {
                $errors[] = "Cannot check tenant database: " . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Get system requirements check.
     */
    public static function checkSystemRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'required' => '8.2.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'laravel_version' => [
                'required' => '11.0.0',
                'current' => app()->version(),
                'status' => version_compare(app()->version(), '11.0.0', '>='),
            ],
            'extensions' => [],
        ];

        // Check required PHP extensions
        $requiredExtensions = ['pdo', 'mbstring', 'json', 'openssl', 'tokenizer'];
        
        foreach ($requiredExtensions as $extension) {
            $requirements['extensions'][$extension] = [
                'required' => true,
                'status' => extension_loaded($extension),
            ];
        }

        return $requirements;
    }

    /**
     * Validate tenant configuration integrity.
     */
    public static function validateTenantIntegrity(): array
    {
        $errors = [];
        $tenantModel = config('tenancy.tenant_model');

        try {
            $tenants = $tenantModel::all();
            
            foreach ($tenants as $tenant) {
                // Validate tenant has required fields
                if (empty($tenant->name)) {
                    $errors[] = "Tenant {$tenant->id} is missing a name.";
                }

                // Validate domain/subdomain based on detection strategy
                $strategy = config('tenancy.detection.strategy');
                
                if ($strategy === 'domain' && empty($tenant->domain)) {
                    $errors[] = "Tenant {$tenant->id} is missing domain for domain-based detection.";
                }
                
                if ($strategy === 'subdomain' && empty($tenant->subdomain)) {
                    $errors[] = "Tenant {$tenant->id} is missing subdomain for subdomain-based detection.";
                }

                // Validate tenant database if using separate strategy
                if (config('tenancy.database.strategy') === 'separate') {
                    $dbErrors = self::validateTenantDatabase($tenant->getTenantId());
                    $errors = array_merge($errors, $dbErrors);
                }
            }
        } catch (\Exception $e) {
            $errors[] = "Cannot validate tenant integrity: " . $e->getMessage();
        }

        return $errors;
    }
}
