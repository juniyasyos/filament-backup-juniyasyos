<?php

use Illuminate\Support\Facades\Route;
use Juniyasyos\FilamentLaravelBackup\Http\Controllers\BackupController;

Route::middleware(['web', 'auth'])->prefix('admin/backup')->name('backup.')->group(function () {
    Route::post('/{backupJob}/retry', [BackupController::class, 'retry'])->name('retry');
    Route::post('/{backupJob}/cancel', [BackupController::class, 'cancel'])->name('cancel');
    Route::get('/{backupJob}/download', [BackupController::class, 'download'])->name('download');
    Route::delete('/{backupJob}', [BackupController::class, 'delete'])->name('delete');
});
