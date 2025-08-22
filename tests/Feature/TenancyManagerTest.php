<?php

namespace Litepie\Tenancy\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Litepie\Tenancy\Models\Tenant;
use Litepie\Tenancy\Contracts\TenancyManagerContract;
use Orchestra\Testbench\TestCase;
use Litepie\Tenancy\TenancyServiceProvider;

class TenancyManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [TenancyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        Config::set('tenancy.tenant_model', Tenant::class);
        Config::set('tenancy.database.strategy', 'separate');
        Config::set('tenancy.detection.strategy', 'domain');
    }

    public function test_can_create_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'config' => ['timezone' => 'UTC'],
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Test Tenant', $tenant->name);
        $this->assertEquals('test.example.com', $tenant->domain);
    }

    public function test_can_activate_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $tenancyManager = app(TenancyManagerContract::class);

        $tenancyManager->setTenant($tenant);

        $this->assertTrue($tenancyManager->hasTenant());
        $this->assertEquals($tenant->id, $tenancyManager->current()->getTenantId());
    }

    public function test_can_execute_in_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $tenancyManager = app(TenancyManagerContract::class);

        $result = $tenancyManager->executeInTenant($tenant, function () {
            return 'executed';
        });

        $this->assertEquals('executed', $result);
    }

    public function test_tenant_configuration(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => ['app_name' => 'Custom App', 'theme' => 'dark'],
        ]);

        $this->assertEquals('Custom App', $tenant->getConfig('app_name'));
        $this->assertEquals('dark', $tenant->getConfig('theme'));
        $this->assertEquals('default', $tenant->getConfig('non_existent', 'default'));
    }

    public function test_tenant_activation_events(): void
    {
        $tenant = Tenant::factory()->create();
        $tenancyManager = app(TenancyManagerContract::class);

        $eventFired = false;
        \Event::listen(\Litepie\Tenancy\Events\TenantActivated::class, function () use (&$eventFired) {
            $eventFired = true;
        });

        $tenancyManager->setTenant($tenant);

        $this->assertTrue($eventFired);
    }
}
