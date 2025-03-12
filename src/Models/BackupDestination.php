<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackup;
use Sushi\Sushi;

/**
 * @property string $path
 * @property string $disk
 */
class BackupDestination extends Model
{
    use Sushi;

    public function getRows(): array
    {
        $data = [];

        foreach (FilamentLaravelBackup::getDisks() as $disk) {
            $data = array_merge($data, FilamentLaravelBackup::getBackupDestinationData($disk));
        }

        return $data;
    }
}
