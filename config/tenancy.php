<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your tenant model. This model should
    | implement the TenantContract interface and extend the base Tenant model.
    |
    */
    'tenant_model' => \Litepie\Tenancy\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Detection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are detected from incoming requests. Multiple
    | strategies are supported for maximum flexibility.
    |
    */
    'detection' => [
        // Detection strategy: domain, subdomain, header, path
        'strategy' => env('TENANCY_DETECTION_STRATEGY', 'domain'),
        
        // Cache tenant lookup to improve performance in production
        'cache_tenant_lookup' => env('TENANCY_CACHE_LOOKUP', true),
        
        // Cache TTL in seconds for tenant lookup results
        'cache_ttl' => env('TENANCY_CACHE_TTL', 3600),
        
        // Cache key prefix for tenant lookups
        'cache_key' => env('TENANCY_CACHE_KEY', 'tenant_lookup'),
        
        // Excluded subdomains for subdomain-based detection
        'excluded_subdomains' => ['www', 'mail', 'ftp', 'admin', 'api', 'cdn', 'static'],
        
        // Header name for header-based tenant detection
        'header' => env('TENANCY_HEADER', 'X-Tenant-ID'),
        
        // Path segment index for path-based detection (0-based)
        'path_segment' => env('TENANCY_PATH_SEGMENT', 0),
        
        // Fallback tenant ID when detection fails
        'fallback_tenant' => env('TENANCY_FALLBACK_TENANT', null),
        
        // Case sensitivity for domain/subdomain matching
        'case_sensitive' => env('TENANCY_CASE_SENSITIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database strategies and connections for multi-tenancy.
    | Supports separate databases or single database with tenant isolation.
    |
    */
    'database' => [
        // Database strategy: separate, single
        'strategy' => env('TENANCY_DATABASE_STRATEGY', 'separate'),
        
        // Landlord database connection (main application database)
        'landlord_connection' => env('TENANCY_LANDLORD_CONNECTION', 'mysql'),
        
        // Tenant database connection template name
        'tenant_connection' => env('TENANCY_TENANT_CONNECTION', 'tenant'),
        
        // Prefix for tenant database names (separate strategy only)
        'tenant_database_prefix' => env('TENANCY_DB_PREFIX', 'tenant_'),
        
        // Path to tenant-specific migrations
        'migrations_path' => env('TENANCY_MIGRATIONS_PATH', 'database/migrations/tenant'),
        
        // Tenant seeder class name
        'tenant_seeder' => env('TENANCY_SEEDER', 'TenantSeeder'),
        
        // Automatically create tenant databases when tenant is created
        'auto_create_database' => env('TENANCY_AUTO_CREATE_DB', true),
        
        // Automatically run migrations for new tenants
        'auto_migrate' => env('TENANCY_AUTO_MIGRATE', false),
        
        // Automatically seed tenant databases after migration
        'auto_seed' => env('TENANCY_AUTO_SEED', false),
        
        // Database connection timeout in seconds
        'connection_timeout' => env('TENANCY_DB_TIMEOUT', 60),
        
        // Maximum number of concurrent tenant database connections
        'max_connections' => env('TENANCY_MAX_CONNECTIONS', 100),
        
        // Enable database connection pooling
        'connection_pooling' => env('TENANCY_CONNECTION_POOLING', true),
        
        // Tenant column name for single database strategy
        'tenant_column' => env('TENANCY_TENANT_COLUMN', 'tenant_id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache isolation strategies for tenants. Each tenant can have
    | isolated cache or share cache with proper prefixing.
    |
    */
    'cache' => [
        // Cache strategy: prefixed, separate, shared
        'strategy' => env('TENANCY_CACHE_STRATEGY', 'prefixed'),
        
        // Cache prefix for tenant isolation
        'tenant_prefix' => env('TENANCY_CACHE_PREFIX', 'tenant'),
        
        // Clear cache when switching tenants (impacts performance)
        'clear_on_tenant_switch' => env('TENANCY_CLEAR_CACHE_ON_SWITCH', false),
        
        // Dedicated cache store for tenant-specific data
        'tenant_store' => env('TENANCY_CACHE_STORE', null),
        
        // Cache TTL for tenant configuration data
        'config_ttl' => env('TENANCY_CONFIG_CACHE_TTL', 3600),
        
        // Enable cache tagging for better cache management
        'enable_tagging' => env('TENANCY_CACHE_TAGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file storage isolation for tenants. Each tenant can have
    | separate storage paths or completely isolated disk configurations.
    |
    */
    'storage' => [
        // Storage strategy: tenant_path, separate_disk, shared
        'strategy' => env('TENANCY_STORAGE_STRATEGY', 'tenant_path'),
        
        // Base path for tenant storage within existing disks
        'tenant_path' => env('TENANCY_STORAGE_PATH', 'tenants'),
        
        // Filesystem disks to isolate per tenant
        'tenant_disks' => ['public', 'local'],
        
        // Directories to automatically create for each tenant
        'tenant_directories' => ['public', 'private', 'uploads', 'exports', 'imports', 'temp'],
        
        // Default disk for tenant storage operations
        'default_disk' => env('TENANCY_DEFAULT_DISK', 'local'),
        
        // Automatically create tenant storage directories
        'auto_create_directories' => env('TENANCY_AUTO_CREATE_DIRS', true),
        
        // Storage path pattern for tenant isolation
        'path_pattern' => env('TENANCY_STORAGE_PATTERN', '{tenant_id}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue handling for tenant-aware jobs. Jobs can automatically
    | maintain tenant context when processed.
    |
    */
    'queue' => [
        // Enable tenant-aware queue processing by default
        'tenant_aware' => env('TENANCY_QUEUE_AWARE', true),
        
        // Default queue name for tenant-specific jobs
        'tenant_queue' => env('TENANCY_QUEUE_NAME', 'tenant'),
        
        // Serialize tenant context with job payload
        'serialize_tenant' => env('TENANCY_SERIALIZE_TENANT', true),
        
        // Jobs that should always run in landlord context
        'landlord_jobs' => [
            // Example: \App\Jobs\SystemMaintenanceJob::class,
        ],
        
        // Maximum retry attempts for tenant jobs
        'max_retries' => env('TENANCY_QUEUE_MAX_RETRIES', 3),
        
        // Delay between retry attempts (seconds)
        'retry_delay' => env('TENANCY_QUEUE_RETRY_DELAY', 60),
        
        // Enable queue worker per tenant
        'per_tenant_workers' => env('TENANCY_PER_TENANT_WORKERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance optimizations for production environments.
    | These settings can significantly impact application performance.
    |
    */
    'performance' => [
        // Cache tenant model instances to reduce database queries
        'cache_tenant_models' => env('TENANCY_CACHE_MODELS', true),
        
        // Lazy load tenant context only when needed
        'lazy_loading' => env('TENANCY_LAZY_LOADING', true),
        
        // Enable memory optimization techniques
        'memory_optimization' => env('TENANCY_MEMORY_OPTIMIZATION', true),
        
        // Enable query caching for tenant operations
        'query_caching' => env('TENANCY_QUERY_CACHING', true),
        
        // Batch operations threshold for bulk tenant operations
        'batch_threshold' => env('TENANCY_BATCH_THRESHOLD', 100),
        
        // Enable response caching per tenant
        'response_caching' => env('TENANCY_RESPONSE_CACHING', false),
        
        // Cache compiled views per tenant
        'view_caching' => env('TENANCY_VIEW_CACHING', true),
        
        // Preload tenant configuration
        'preload_config' => env('TENANCY_PRELOAD_CONFIG', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security features for tenant isolation and access control.
    | These settings are critical for multi-tenant data protection.
    |
    */
    'security' => [
        // Enable strict tenant isolation (recommended for production)
        'strict_isolation' => env('TENANCY_STRICT_ISOLATION', true),
        
        // Validate tenant access on each request
        'validate_tenant_access' => env('TENANCY_VALIDATE_ACCESS', true),
        
        // Enable comprehensive audit logging
        'audit_logging' => env('TENANCY_AUDIT_LOGGING', false),
        
        // Log all tenant switches for debugging
        'log_tenant_switches' => env('TENANCY_LOG_SWITCHES', false),
        
        // Prevent cross-tenant data access attempts
        'prevent_cross_tenant_access' => env('TENANCY_PREVENT_CROSS_ACCESS', true),
        
        // Enable rate limiting per tenant
        'rate_limiting' => env('TENANCY_RATE_LIMITING', false),
        
        // Maximum requests per tenant per minute
        'rate_limit_per_minute' => env('TENANCY_RATE_LIMIT', 1000),
        
        // Enable CSRF protection per tenant
        'csrf_protection' => env('TENANCY_CSRF_PROTECTION', true),
        
        // Encrypt tenant configuration data
        'encrypt_config' => env('TENANCY_ENCRYPT_CONFIG', false),
        
        // Enable IP whitelist per tenant
        'ip_whitelist' => env('TENANCY_IP_WHITELIST', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configure debugging options for development environments.
    | Disable these in production for performance and security.
    |
    */
    'debug' => [
        // Enable debug mode (inherits from APP_DEBUG by default)
        'enabled' => env('TENANCY_DEBUG', env('APP_DEBUG', false)),
        
        // Log tenant detection attempts and results
        'log_detection' => env('TENANCY_DEBUG_DETECTION', false),
        
        // Log database connection switches
        'log_database_switches' => env('TENANCY_DEBUG_DB', false),
        
        // Log all tenant context switches
        'log_tenant_switches' => env('TENANCY_DEBUG_SWITCHES', false),
        
        // Include tenant info in response headers (development only)
        'tenant_headers' => env('TENANCY_DEBUG_HEADERS', false),
        
        // Enable detailed query logging per tenant
        'query_logging' => env('TENANCY_DEBUG_QUERIES', false),
        
        // Log performance metrics
        'performance_logging' => env('TENANCY_DEBUG_PERFORMANCE', false),
        
        // Enable memory usage tracking
        'memory_tracking' => env('TENANCY_DEBUG_MEMORY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific tenancy features. Useful for gradual
    | rollouts and feature testing in production environments.
    |
    */
    'features' => [
        // Enable automatic tenant bootstrapping on request
        'auto_bootstrap' => env('TENANCY_AUTO_BOOTSTRAP', true),
        
        // Enable tenant-aware middleware stack
        'middleware' => env('TENANCY_MIDDLEWARE', true),
        
        // Enable tenant-aware Artisan commands
        'commands' => env('TENANCY_COMMANDS', true),
        
        // Enable tenant-aware queue processing
        'queues' => env('TENANCY_QUEUES', true),
        
        // Enable tenant lifecycle events
        'events' => env('TENANCY_EVENTS', true),
        
        // Enable tenant-aware broadcasting
        'broadcasting' => env('TENANCY_BROADCASTING', false),
        
        // Enable tenant-aware notifications
        'notifications' => env('TENANCY_NOTIFICATIONS', false),
        
        // Enable multi-database transactions
        'multi_db_transactions' => env('TENANCY_MULTI_DB_TRANSACTIONS', false),
        
        // Enable tenant-aware authentication
        'tenant_auth' => env('TENANCY_TENANT_AUTH', false),
        
        // Enable automatic tenant subdomain creation
        'auto_subdomain' => env('TENANCY_AUTO_SUBDOMAIN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Health Checks
    |--------------------------------------------------------------------------
    |
    | Configure monitoring, health checks, and alerting for production
    | environments. Critical for maintaining system reliability.
    |
    */
    'monitoring' => [
        // Enable automated health checks
        'health_checks' => env('TENANCY_HEALTH_CHECKS', true),
        
        // Health check interval in seconds
        'health_check_interval' => env('TENANCY_HEALTH_CHECK_INTERVAL', 300),
        
        // Enable metrics collection and storage
        'metrics' => env('TENANCY_METRICS', false),
        
        // Metrics storage driver (database, redis, file)
        'metrics_driver' => env('TENANCY_METRICS_DRIVER', 'database'),
        
        // Enable real-time performance monitoring
        'performance_monitoring' => env('TENANCY_PERFORMANCE_MONITORING', false),
        
        // Enable alerting for tenant issues
        'alerting' => env('TENANCY_ALERTING', false),
        
        // Alert channels (mail, slack, webhook)
        'alert_channels' => ['mail'],
        
        // Enable uptime monitoring per tenant
        'uptime_monitoring' => env('TENANCY_UPTIME_MONITORING', false),
        
        // Monitor tenant resource usage
        'resource_monitoring' => env('TENANCY_RESOURCE_MONITORING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Recovery
    |--------------------------------------------------------------------------
    |
    | Configure backup and disaster recovery options for tenant data.
    | Essential for data protection and business continuity.
    |
    */
    'backup' => [
        // Enable automatic backups
        'enabled' => env('TENANCY_BACKUP_ENABLED', false),
        
        // Backup frequency (daily, weekly, monthly)
        'frequency' => env('TENANCY_BACKUP_FREQUENCY', 'daily'),
        
        // Backup retention period in days
        'retention_days' => env('TENANCY_BACKUP_RETENTION', 30),
        
        // Backup storage disk
        'storage_disk' => env('TENANCY_BACKUP_DISK', 's3'),
        
        // Include tenant files in backups
        'include_files' => env('TENANCY_BACKUP_FILES', true),
        
        // Compress backup files
        'compression' => env('TENANCY_BACKUP_COMPRESSION', true),
        
        // Encrypt backup files
        'encryption' => env('TENANCY_BACKUP_ENCRYPTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configure integrations with external services and tools.
    |
    */
    'integrations' => [
        // Laravel Octane compatibility
        'octane' => env('TENANCY_OCTANE_SUPPORT', false),
        
        // Laravel Horizon support
        'horizon' => env('TENANCY_HORIZON_SUPPORT', false),
        
        // Laravel Telescope integration
        'telescope' => env('TENANCY_TELESCOPE_SUPPORT', false),
        
        // Enable API versioning per tenant
        'api_versioning' => env('TENANCY_API_VERSIONING', false),
        
        // Third-party service isolation
        'service_isolation' => env('TENANCY_SERVICE_ISOLATION', true),
    ],
];
