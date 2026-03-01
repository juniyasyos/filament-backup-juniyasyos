<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backup_job_id')->nullable(); // Relation to backup_jobs
            $table->string('session_id')->nullable(); // Group related logs

            // Log classification
            $table->enum('level', [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug'
            ])->default('info');
            $table->string('category')->default('backup'); // backup, storage, validation, etc
            $table->string('event'); // Event name: backup_started, file_processed, etc

            // Log content
            $table->string('message'); // Main log message
            $table->text('description')->nullable(); // Detailed description
            $table->json('context')->nullable(); // Additional context data
            $table->json('metadata')->nullable(); // Extra metadata

            // Performance metrics
            $table->decimal('execution_time', 10, 4)->nullable(); // Seconds
            $table->unsignedBigInteger('memory_usage')->nullable(); // Bytes
            $table->integer('files_processed')->nullable();
            $table->unsignedBigInteger('bytes_processed')->nullable();

            // Error details (if applicable)
            $table->string('error_code')->nullable();
            $table->text('error_stack')->nullable();
            $table->json('error_context')->nullable();

            // Source tracking
            $table->string('source_class')->nullable(); // Which class generated the log
            $table->string('source_method')->nullable(); // Which method
            $table->integer('source_line')->nullable(); // Line number

            // Environment info
            $table->string('php_version')->nullable();
            $table->string('laravel_version')->nullable();
            $table->string('environment')->nullable(); // production, staging, local

            $table->timestamps();

            // Indexes
            $table->index(['backup_job_id', 'created_at']);
            $table->index(['level', 'created_at']);
            $table->index(['category', 'event']);
            $table->index(['session_id', 'created_at']);
            $table->index(['created_at']); // For cleanup queries

            // Foreign key constraint
            $table->foreign('backup_job_id')
                ->references('id')
                ->on('backup_jobs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
