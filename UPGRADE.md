# Filament Spatie Laravel Backup Plugin

Plugin ini menyediakan integrasi **Spatie Laravel Backup** dengan **Filament v3**, memungkinkan Anda untuk mengelola backup langsung dari panel Filament.

## 📦 Instalasi

Jalankan perintah berikut untuk menginstal paket ini:

```sh
composer require juniyasyos/filament-spatie-laravel-backup
```

Setelah itu, daftarkan plugin di dalam **PanelProvider** Anda:

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
            ->plugin(FilamentLaravelBackupPlugin::make());
    }
}
```

Kemudian, publikasikan aset plugin:

```sh
php artisan filament:assets
```

---

## ⚙️ Konfigurasi

Anda dapat mengatur berbagai opsi plugin menggunakan method chaining pada objek plugin:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Backups;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugin(
                FilamentLaravelBackupPlugin::make()
                    ->usingPage(Backups::class) // Gunakan halaman kustom untuk backup
                    ->usingQueue('backup-queue') // Tentukan queue untuk proses backup
                    ->usingPollingInterval('10s') // Interval polling (default: 4s)
                    ->statusListRecordsTable(false) // Sembunyikan tabel status backup (default: true)
                    ->showBackupSize(true) // Tampilkan ukuran backup
                    ->allowManualBackup(true) // Izinkan backup manual
            );
    }
}
```

---

## 🚀 Fitur

✅ **Integrasi penuh dengan Filament v3**  
✅ **Dukungan antrian (Queue) untuk backup**  
✅ **Konfigurasi polling interval**  
✅ **Tabel status backup yang dapat disembunyikan**  
✅ **Dukungan halaman kustom untuk manajemen backup**  
✅ **Menampilkan ukuran backup**  
✅ **Dukungan backup manual langsung dari panel**  

---

## 🛠️ Cara Menggunakan

Setelah menginstal dan mengonfigurasi plugin, Anda dapat mengakses halaman **Backup** di panel Filament untuk:  

- **Melihat daftar backup yang tersedia**  
- **Menjalankan backup manual** (jika diizinkan)  
- **Menghapus backup lama**  
- **Melihat status backup terbaru**  

---

## ❓ Troubleshooting

Jika Anda mengalami masalah, lakukan langkah berikut:  

1. **Pastikan queue berjalan** jika Anda menggunakan antrian untuk backup:
   ```sh
   php artisan queue:work
   ```
   
2. **Periksa konfigurasi backup** di `config/backup.php` untuk memastikan penyimpanan dan strategi backup sudah benar.

3. **Lihat log error** jika terjadi masalah:
   ```sh
   tail -f storage/logs/laravel.log
   ```

Jika masih ada kendala, silakan **buka issue** di repository ini dengan detail error yang jelas.  

---

## 📜 Lisensi

Proyek ini dilisensikan di bawah **MIT License**.