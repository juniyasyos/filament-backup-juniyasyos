<?php

namespace Juniyasyos\FilamentLaravelBackup\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Jobs\ImprovedBackupJob;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;

class BackupController extends Controller
{
    /**
     * Retry a failed backup job
     */
    public function retry(BackupJob $backupJob): JsonResponse
    {
        if (!$backupJob->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'This backup job cannot be retried anymore.',
            ], 400);
        }

        try {
            // Increment retry count and reset status
            $backupJob->incrementRetry();

            // Determine the option from the original job
            $option = match ($backupJob->type) {
                'database_only' => Option::ONLY_DB,
                'files_only' => Option::ONLY_FILES,
                default => Option::ALL,
            };

            // Dispatch new backup job
            $newJob = ImprovedBackupJob::dispatch(
                $option,
                $backupJob->options['custom_filename'] ?? null,
                $backupJob->user_id,
                $backupJob->user_type,
                array_merge($backupJob->options, [
                    'initiated_via' => 'retry',
                    'original_job_id' => $backupJob->id,
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup retry has been queued successfully.',
                'retry_count' => $backupJob->retry_count,
                'max_retries' => $backupJob->max_retries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue backup retry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an active backup job
     */
    public function cancel(BackupJob $backupJob): JsonResponse
    {
        if (!$backupJob->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This backup job is not active and cannot be cancelled.',
            ], 400);
        }

        try {
            $backupJob->cancel('Cancelled by user request');

            return response()->json([
                'success' => true,
                'message' => 'Backup job has been cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel backup job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a completed backup file
     */
    public function download(BackupJob $backupJob): Response|JsonResponse
    {
        if (!$backupJob->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Backup is not completed yet.',
            ], 400);
        }

        if (!$backupJob->path) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file path is not available.',
            ], 404);
        }

        $disk = $backupJob->disk;
        $path = $backupJob->path;

        try {
            $filename = $backupJob->filename ?? basename($path);

            // Prefer stream from storage disk for compatibility with local and cloud disks
            if (!\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found on configured disk.',
                ], 404);
            }

            $stream = \Illuminate\Support\Facades\Storage::disk($disk)->readStream($path);

            if ($stream === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to open backup stream.',
                ], 500);
            }

            return response()->streamDownload(function () use ($stream) {
                while (!feof($stream)) {
                    echo fread($stream, 1024 * 8);
                }
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, $filename, [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup job and its associated file
     */
    public function delete(BackupJob $backupJob): JsonResponse
    {
        if ($backupJob->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an active backup job. Please cancel it first.',
            ], 400);
        }

        if ($backupJob->is_protected) {
            return response()->json([
                'success' => false,
                'message' => 'This backup is protected and cannot be deleted.',
            ], 403);
        }

        try {
            // Delete the backup file if it exists
            if ($backupJob->path && $backupJob->disk) {
                $disk = $backupJob->disk;
                $path = $backupJob->path;

                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }

            // Delete associated logs
            $backupJob->logs()->delete();

            // Delete the job record
            $backupJob->delete();

            return response()->json([
                'success' => true,
                'message' => 'Backup job and associated file have been deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage(),
            ], 500);
        }
    }
}
