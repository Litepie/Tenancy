<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->string('subdomain')->unique()->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['domain', 'is_active']);
            $table->index(['subdomain', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
