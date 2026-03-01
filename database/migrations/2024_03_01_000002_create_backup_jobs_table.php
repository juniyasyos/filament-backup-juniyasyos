<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Unique identifier for the job
            $table->string('name'); // Human readable name for the job
            $table->enum('type', ['full', 'database_only', 'files_only'])->default('full');
            $table->enum('status', [
                'pending',
                'queued',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'timeout'
            ])->default('pending');

            // Job details
            $table->json('options')->nullable(); // Backup options and parameters
            $table->string('disk')->nullable(); // Target storage disk
            $table->string('filename')->nullable(); // Generated filename
            $table->string('path')->nullable(); // Full path to backup file

            // Progress tracking
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->string('current_step')->nullable(); // Current processing step
            $table->json('steps')->nullable(); // All steps and their status

            // Size and timing
            $table->unsignedBigInteger('file_size')->nullable(); // In bytes
            $table->integer('duration')->nullable(); // In seconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Error handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable(); // Stack trace, etc
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('next_retry_at')->nullable();

            // User and audit
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable(); // Morph relation
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Queue information
            $table->string('queue_name')->nullable();
            $table->string('connection')->nullable();
            $table->json('job_payload')->nullable(); // Original job payload

            // Cleanup and retention
            $table->boolean('should_cleanup')->default(true);
            $table->timestamp('cleanup_at')->nullable(); // When to auto-delete
            $table->boolean('is_protected')->default(false); // Prevent auto-cleanup

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'user_type']);
            $table->index(['disk', 'status']);
            $table->index(['cleanup_at', 'should_cleanup']);
            $table->index(['next_retry_at', 'status']);

            // Foreign key constraints (optional, depends on user model)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
    }
};
