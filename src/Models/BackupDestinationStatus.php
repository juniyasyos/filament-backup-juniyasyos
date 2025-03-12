<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackup;
use Sushi\Sushi;

class BackupDestinationStatus extends Model
{
    use Sushi;

    public function getRows(): array
    {
        return FilamentLaravelBackup::getBackupDestinationStatusData();
    }
}
