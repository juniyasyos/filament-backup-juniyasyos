<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;
use Juniyasyos\FilamentLaravelBackup\Services\BackupService;

class TestMinioConnectionCommand extends Command
{
    protected $signature = 'backup:test-minio';
    protected $description = 'Test MinIO connection with simple CRUD (create, read, delete)';

    public function handle(BackupService $backupService)
    {
        $disk = 'minio';
        $result = $backupService->testSimpleCrud($disk);

        $this->info('Testing MinIO connection on disk: ' . $disk);
        $this->line('Created: ' . ($result['created'] ? 'yes' : 'no'));
        $this->line('Read: ' . ($result['read'] ? 'yes' : 'no'));
        $this->line('Deleted: ' . ($result['deleted'] === null ? 'skipped' : ($result['deleted'] ? 'yes' : 'no')));
        if (!empty($result['errors'])) {
            $this->error('Errors: ' . implode('; ', $result['errors']));
        } else {
            $this->info('No errors. MinIO connection is working.');
        }
    }
}
