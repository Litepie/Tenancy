<?php

namespace Litepie\Tenancy\Commands;

use Illuminate\Console\Command;
use Litepie\Tenancy\Support\ConfigurationValidator;

class TenantDiagnoseCommand extends Command
{
    protected $signature = 'tenant:diagnose 
                           {--check-requirements : Check system requirements}
                           {--check-config : Check configuration validity}
                           {--check-integrity : Check tenant data integrity}
                           {--fix : Attempt to fix issues automatically}';
    
    protected $description = 'Diagnose tenancy configuration and system health';

    public function handle(): int
    {
        $this->info('🔍 Litepie Tenancy Diagnostic Tool');
        $this->newLine();

        $checkRequirements = $this->option('check-requirements') || !$this->hasSpecificCheck();
        $checkConfig = $this->option('check-config') || !$this->hasSpecificCheck();
        $checkIntegrity = $this->option('check-integrity') || !$this->hasSpecificCheck();
        $fix = $this->option('fix');

        $hasErrors = false;

        if ($checkRequirements) {
            $hasErrors = $this->checkSystemRequirements() || $hasErrors;
        }

        if ($checkConfig) {
            $hasErrors = $this->checkConfiguration($fix) || $hasErrors;
        }

        if ($checkIntegrity) {
            $hasErrors = $this->checkTenantIntegrity($fix) || $hasErrors;
        }

        $this->newLine();
        
        if ($hasErrors) {
            $this->error('❌ Issues found. Please review the errors above.');
            return self::FAILURE;
        }

        $this->info('✅ All checks passed! Your tenancy setup looks good.');
        return self::SUCCESS;
    }

    protected function hasSpecificCheck(): bool
    {
        return $this->option('check-requirements') || 
               $this->option('check-config') || 
               $this->option('check-integrity');
    }

    protected function checkSystemRequirements(): bool
    {
        $this->info('📋 Checking System Requirements...');
        
        $requirements = ConfigurationValidator::checkSystemRequirements();
        $hasErrors = false;

        // Check PHP version
        if ($requirements['php_version']['status']) {
            $this->info("✅ PHP version: {$requirements['php_version']['current']}");
        } else {
            $this->error("❌ PHP version: {$requirements['php_version']['current']} (requires {$requirements['php_version']['required']}+)");
            $hasErrors = true;
        }

        // Check Laravel version
        if ($requirements['laravel_version']['status']) {
            $this->info("✅ Laravel version: {$requirements['laravel_version']['current']}");
        } else {
            $this->error("❌ Laravel version: {$requirements['laravel_version']['current']} (requires {$requirements['laravel_version']['required']}+)");
            $hasErrors = true;
        }

        // Check PHP extensions
        foreach ($requirements['extensions'] as $extension => $status) {
            if ($status['status']) {
                $this->info("✅ PHP extension '{$extension}' is loaded");
            } else {
                $this->error("❌ PHP extension '{$extension}' is missing");
                $hasErrors = true;
            }
        }

        $this->newLine();
        return $hasErrors;
    }

    protected function checkConfiguration(bool $fix = false): bool
    {
        $this->info('⚙️  Checking Configuration...');
        
        $errors = ConfigurationValidator::validate();
        
        if (empty($errors)) {
            $this->info('✅ Configuration is valid');
            $this->newLine();
            return false;
        }

        foreach ($errors as $error) {
            $this->error("❌ {$error}");
        }

        if ($fix) {
            $this->info('🔧 Attempting to fix configuration issues...');
            // Add auto-fix logic here
            $this->warn('Auto-fix for configuration issues is not yet implemented.');
        }

        $this->newLine();
        return true;
    }

    protected function checkTenantIntegrity(bool $fix = false): bool
    {
        $this->info('🏢 Checking Tenant Integrity...');
        
        try {
            $errors = ConfigurationValidator::validateTenantIntegrity();
            
            if (empty($errors)) {
                $this->info('✅ All tenants are properly configured');
                $this->newLine();
                return false;
            }

            foreach ($errors as $error) {
                $this->error("❌ {$error}");
            }

            if ($fix) {
                $this->info('🔧 Attempting to fix tenant integrity issues...');
                // Add auto-fix logic here
                $this->warn('Auto-fix for tenant integrity issues is not yet implemented.');
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to check tenant integrity: {$e->getMessage()}");
            $this->newLine();
            return true;
        }

        $this->newLine();
        return true;
    }
}
