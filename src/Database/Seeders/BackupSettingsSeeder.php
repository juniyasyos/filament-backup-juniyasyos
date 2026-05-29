<?php

namespace Juniyasyos\FilamentLaravelBackup\Database\Seeders;

use Illuminate\Database\Seeder;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
use Juniyasyos\FilamentLaravelBackup\Support\BackupSettingsDefaults;

class BackupSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (BackupSettingsDefaults::all() as $setting) {
            BackupSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'name' => $setting['name'],
                    'description' => $setting['description'] ?? null,
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'value' => $setting['value'],
                    'is_active' => $setting['is_active'] ?? true,
                    'sort_order' => $setting['sort_order'] ?? 0,
                ]
            );
        }
    }
}
