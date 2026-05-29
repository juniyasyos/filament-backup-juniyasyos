<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('backup_settings')) {
            Schema::dropIfExists('backup_settings');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('backup_settings')) {
            Schema::create('backup_settings', function ($table): void {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('group')->default('backup');
                $table->string('type')->default('string');
                $table->text('value')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }
};
