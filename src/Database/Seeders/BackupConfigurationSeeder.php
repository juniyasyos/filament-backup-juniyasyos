<?php

namespace Juniyasyos\FilamentLaravelBackup\Database\Seeders;

use Illuminate\Database\Seeder;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;

class BackupConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        BackupConfiguration::ensureDefaults();
    }
}
