<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('store.settings.table', 'store_settings'), function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('group', 80)->default('profile')->index();
            $table->json('value')->nullable();
            $table->string('type', 40)->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('store.settings.table', 'store_settings'));
    }
};
