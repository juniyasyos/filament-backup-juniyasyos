# Filament Advanced Backup Plugin

[![PHP Version Require](https://poser.pugx.org/juniyasyos/filament-backup/require/php)](https://packagist.org/packages/juniyasyos/filament-backup)
[![Latest Stable Version](https://poser.pugx.org/juniyasyos/filament-backup/v)](https://packagist.org/packages/juniyasyos/filament-backup)
[![Total Downloads](https://poser.pugx.org/juniyasyos/filament-backup/downloads)](https://packagist.org/packages/juniyasyos/filament-backup)
[![License](https://poser.pugx.org/juniyasyos/filament-backup/license)](https://packagist.org/packages/juniyasyos/filament-backup)

Advanced backup management plugin for FilamentPHP with real-time progress tracking, multiple storage backends, smart retry logic, and comprehensive logging. Built on top of [spatie/laravel-backup](https://spatie.be/docs/laravel-backup/v7/introduction).

## ✨ Features

- 🚀 **Real-time Progress Tracking** - Monitor backup progress with live updates
- 💾 **Multiple Storage Backends** - Local, Amazon S3, Google Cloud Storage support
- 🔄 **Smart Retry Logic** - Automatic retry with exponential backoff
- 📊 **Advanced Dashboard** - Statistics, job monitoring, and management
- 🔔 **Smart Notifications** - Email, database, and broadcast notifications
- ⚙️ **Dynamic Configuration** - Manage settings via elegant UI
- 📝 **Comprehensive Logging** - Detailed logs with performance metrics
- 🔒 **Security Features** - Role-based access, encryption support
- 🎯 **Job Management** - Cancel, retry, download backups with ease

## Installation

Install the package via Composer:

```bash
composer require juniyasyos/filament-backup
```

## Configuration

### 1. Publish and Run Migrations

```bash
# Publish migrations
php artisan vendor:publish --tag="filament-backup-migrations"

# Run migrations
php artisan migrate
```

### 2. Publish Configuration (Optional)

```bash
# Publish configuration file
php artisan vendor:publish --tag="filament-backup-config"
```

### 3. Publish Assets (Optional)

```bash
# Publish translation files
php artisan vendor:publish --tag="filament-backup-translations"
```

## Usage

### Register the Plugin

Add the plugin to your Filament Panel Provider (e.g., `AdminPanelProvider`):

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->withSettingsPage() // Include settings management page
                    ->usingTimeout(7200) // 2 hours timeout
                    ->usingQueue('backup') // Use dedicated backup queue
                    ->withStatusListRecordsTable() // Show backup destination status
            );
    }
}
```

### Plugin Configuration Options

```php
FilamentLaravelBackupPlugin::make()
    ->usingPage(CustomBackupPage::class) // Use custom backup page
    ->withSettingsPage() // Include settings page (default: true)
    ->withoutSettingsPage() // Exclude settings page
    ->usingTimeout(3600) // Set backup timeout in seconds
    ->usingQueue('backup') // Use specific queue name
    ->usingPolingInterval('5s') // Set UI polling interval
    ->withStatusListRecordsTable() // Show destination status (default: true)
    ->withoutStatusListRecordsTable() // Hide destination status
```

## Configuration

### Storage Settings

Configure storage backends via the Settings page in Filament Admin or by editing the config file:

#### Local Storage
```php
'storage' => [
    'default_disk' => 'local',
    'local' => [
        'path' => 'storage/app/backup',
    ],
]
```

#### Amazon S3
```php
'storage' => [
    'default_disk' => 's3',
    's3' => [
        'bucket' => env('BACKUP_S3_BUCKET'),
        'region' => env('BACKUP_S3_REGION', 'us-east-1'),
        'key' => env('BACKUP_S3_KEY'),
        'secret' => env('BACKUP_S3_SECRET'),
    ],
]
```

### Environment Variables

Add these to your `.env` file for S3 configuration:

```env
BACKUP_S3_BUCKET=your-backup-bucket
BACKUP_S3_REGION=us-east-1
BACKUP_S3_KEY=your-access-key
BACKUP_S3_SECRET=your-secret-key

# Optional: Notification recipients
BACKUP_NOTIFICATION_RECIPIENTS=admin@yoursite.com,backup@yoursite.com

# Optional: Encryption key for backups
BACKUP_ENCRYPTION_KEY=your-encryption-key
```

## Usage Examples

### Creating Backups

1. Navigate to **Backup > Backups** in Filament Admin
2. Click **Create Backup**
3. Choose backup type:
   - **Full Backup** - Database + Files
   - **Database Only** - Database dump only
   - **Files Only** - Application files only
4. Monitor real-time progress
5. Download or manage completed backups

### Managing Settings

1. Navigate to **Backup > Settings**
2. Configure storage backends
3. Set notification preferences
4. Adjust timeout and cleanup settings
5. Test storage connections

### Console Commands

```bash
# Cleanup old backup jobs
php artisan backup:cleanup-jobs

# Cleanup old logs (keep last 90 days)
php artisan backup:cleanup-logs --days=90

# Dry run to see what would be deleted
php artisan backup:cleanup-jobs --dry-run
php artisan backup:cleanup-logs --dry-run --days=30
```

## Advanced Features

### Real-time Progress Tracking

Monitor backup progress with detailed step-by-step tracking:
- Storage validation
- Database backup creation
- File collection and compression
- Transfer to storage
- Verification and cleanup

### Smart Notifications

Receive notifications via:
- **Email** - Detailed progress and completion reports
- **Database** - Real-time UI notifications
- **Broadcasting** - Live updates (optional)

### Backup Verification

Automatic integrity checks:
- File size validation
- ZIP integrity verification
- Storage accessibility tests
- Checksum validation (planned)

### Error Handling & Retry Logic

Robust error handling with:
- Automatic retry with exponential backoff
- Detailed error logging and context
- User-friendly error messages
- Recovery suggestions

## Customization

### Custom Backup Page

```php
<?php

namespace App\Filament\Pages;

use Juniyasyos\FilamentLaravelBackup\Pages\Backups as BaseBackups;

class CustomBackups extends BaseBackups
{
    protected static ?string $navigationIcon = 'heroicon-o-server';
    
    public function getHeading(): string | Htmlable
    {
        return 'Application Backups';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System Administration';
    }
}
```

Then register it:

```php
FilamentLaravelBackupPlugin::make()
    ->usingPage(CustomBackups::class)
```

### Custom Notifications

Extend notification classes to customize behavior:

```php
<?php

namespace App\Notifications;

use Juniyasyos\FilamentLaravelBackup\Notifications\BackupCompletedNotification as BaseNotification;

class CustomBackupNotification extends BaseNotification
{
    // Customize notification behavior
}
```

## API Reference

### Models

- `BackupSetting` - Manage dynamic configuration
- `BackupJob` - Track backup jobs and progress
- `BackupLog` - Store detailed execution logs

### Services

- `BackupService` - Core backup operations
- Storage validation and management
- Progress tracking and reporting

### Jobs

- `ImprovedBackupJob` - Enhanced backup execution with progress tracking

## Requirements

- PHP 8.2+
- Laravel 10.0+
- FilamentPHP 3.0+
- spatie/laravel-backup 8.0+

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Shuvro Roy](https://github.com/shuvroroy)
- [Ahmad Ilyas](https://github.com/juniyasyos)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Customising the polling interval

You can customise the polling interval for the `Backups` by following the steps below:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->usingPolingInterval('10s') // default value is 4s
            );
    }
}
```

## Customising the queue

You can customise the queue name for the `Backups` by following the steps below:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->usingQueue('my-queue') // default value is null
            );
    }
}
```

## Customising the timeout

You can customise the timeout for the backup job by following the steps below:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->timeout(120) // default value is max_execution_time from php.ini, or 30s if it wasn't defined
            );
    }
}
```

For more details refer to the [set_time_limit](https://www.php.net/manual/en/function.set-time-limit.php) function.

You can also disable the timeout altogether to let the job run as long as needed:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->noTimeout()
            );
    }
}
```

## Customising who can access the page

You can customise who can access the `Backups` page by adding an `authorize` method to the plugin.
The method should return a boolean indicating whether the user is authorised to access the page.

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                     ->authorize(fn (): bool => auth()->user()->email === 'admin@example.com'),
            );
    }
}
```

## Upgrading

Please see [UPGRADE](UPGRADE.md) for details on how to upgrade 1.X to 2.0.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Shuvro Roy](https://github.com/shuvroroy)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
