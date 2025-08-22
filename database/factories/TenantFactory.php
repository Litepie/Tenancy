<?php

namespace Litepie\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Litepie\Tenancy\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'domain' => $this->faker->unique()->domainName(),
            'subdomain' => $this->faker->unique()->slug(),
            'config' => [
                'timezone' => $this->faker->timezone(),
                'locale' => 'en',
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withConfig(array $config): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => array_merge($attributes['config'] ?? [], $config),
        ]);
    }
}
