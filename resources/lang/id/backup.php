<?php

return [

    'components' => [
        'backup_destination_list' => [
            'table' => [
                'actions' => [
                    'download' => 'Unduh',
                    'delete' => 'Hapus',
                ],

                'fields' => [
                    'path' => 'Lokasi',
                    'disk' => 'Penyimpanan',
                    'date' => 'Tanggal',
                    'size' => 'Ukuran',
                ],

                'filters' => [
                    'disk' => 'Penyimpanan',
                ],
            ],
        ],

        'backup_destination_status_list' => [
            'table' => [
                'fields' => [
                    'name' => 'Nama',
                    'disk' => 'Penyimpanan',
                    'healthy' => 'Sehat',
                    'amount' => 'Jumlah',
                    'newest' => 'Terbaru',
                    'used_storage' => 'Penyimpanan Terpakai',
                ],
            ],
        ],
    ],

    'pages' => [
        'settings' => [
            'heading' => 'Pengaturan Cadangan',

            'general' => [
                'section' => 'Konfigurasi Cadangan',
                'description' => 'Pengaturan umum untuk proses cadangan',
                'timeout_label' => 'Timeout Cadangan (detik)',
                'timeout_helper' => 'Batas waktu maksimum untuk proses cadangan (detik). Default: 3600',
                'queue_label' => 'Nama Antrian',
                'queue_helper' => 'Antrian tempat tugas cadangan dikirim (mis. default, high). Biarkan default kecuali perlu dipisah',
                'cleanup_label' => 'Aktifkan Pembersihan Otomatis',
                'cleanup_helper' => 'Secara otomatis menghapus cadangan lama sesuai jumlah hari pembersihan',
                'cleanup_days_label' => 'Hapus Setelah (hari)',
                'cleanup_days_helper' => 'Jumlah hari penyimpanan cadangan sebelum dibersihkan otomatis',
                'notifications_enabled_label' => 'Notifikasi Email',
                'notifications_enabled_helper' => 'Aktifkan pengiriman notifikasi email untuk event cadangan',
            ],

            'storage' => [
                'section' => 'Konfigurasi Penyimpanan',
                'description' => 'Atur tempat penyimpanan cadangan',
                'default_disk_label' => 'Penyimpanan Default',
                'default_disk_helper' => 'Penyimpanan utama untuk cadangan',
                'local_section' => 'Penyimpanan Lokal',
                'local_description' => 'Konfigurasi penyimpanan pada file system lokal',
                'local_path_label' => 'Path Penyimpanan Lokal',
                'local_path_helper' => 'Path relatif di aplikasi untuk cadangan (mis. storage/app/backup). Gunakan path absolut jika perlu',
                's3_section' => 'Penyimpanan Amazon S3',
                's3_description' => 'Konfigurasi penyimpanan cloud Amazon S3',
                's3_bucket_label' => 'Nama Bucket S3',
                's3_bucket_helper' => 'Bucket S3 tempat cadangan disimpan (mis. my-app-backups)',
                's3_region_label' => 'Region S3',
                's3_region_helper' => 'Region Amazon S3',
                's3_key_label' => 'S3 Access Key',
                's3_key_helper' => 'Access Key ID untuk S3. Untuk keamanan, gunakan environment variable jika memungkinkan',
                's3_secret_label' => 'S3 Secret Key',
                's3_secret_helper' => 'Secret key untuk S3. Simpan dengan aman (disarankan di env vars)',
                'gcs_section' => 'Google Cloud',
                'status' => [
                    'available' => 'Tersedia dan dikonfigurasi',
                    'requires_configuration' => 'Perlu konfigurasi',
                    'not_configured' => 'Belum dikonfigurasi',
                ],
            ],

            'buttons' => [
                'save' => 'Simpan Pengaturan',
                'test_storage' => 'Uji Penyimpanan',
                'reset' => 'Kembali ke Default',
            ],

            'recent' => [
                'heading' => 'Perubahan Konfigurasi Terbaru',
                'description' => 'Log perubahan terbaru pada konfigurasi cadangan.',
                'empty' => 'Belum ada perubahan terbaru.',
            ],

            'notifications' => [
                'section' => 'Notifikasi Email',
                'description' => 'Atur pengaturan notifikasi email',
                'on_success_label' => 'Notifikasi Saat Berhasil',
                'on_success_helper' => 'Kirim notifikasi saat cadangan berhasil',
                'on_failure_label' => 'Notifikasi Saat Gagal',
                'on_failure_helper' => 'Kirim notifikasi saat cadangan gagal',
                'progress_updates_label' => 'Pembaruan Progres',
                'progress_updates_helper' => 'Kirim pembaruan progres berkala selama proses cadangan',
                'recipients_section' => 'Penerima',
                'recipients_description' => 'Atur siapa yang menerima notifikasi',
                'recipient_email_label' => 'Email',
                'notify_user_label' => 'Beri Tahu Pembuat Cadangan',
                'notify_user_helper' => 'Kirim notifikasi ke pengguna yang memulai cadangan',
            ],

            'security' => [
                'access_section' => 'Kontrol Akses',
                'access_description' => 'Pengaturan keamanan dan kontrol akses',
                'require_permission_label' => 'Memerlukan Izin',
                'require_permission_helper' => 'Membutuhkan izin khusus untuk mengakses fitur cadangan',
                'allowed_roles_label' => 'Peran yang Diizinkan (pisah koma)',
                'allowed_roles_helper' => 'Peran yang dapat mengakses fitur cadangan. Pisahkan dengan koma, contoh: admin,backup-manager',
                'file_section' => 'Keamanan Berkas',
                'file_description' => 'Pengaturan keamanan dan enkripsi berkas',
                'encrypt_backups_label' => 'Enkripsi Cadangan',
                'encrypt_backups_helper' => 'Enkripsi berkas cadangan untuk keamanan tambahan',
                'encryption_key_label' => 'Kunci Enkripsi',
                'encryption_key_helper' => 'Opsional: masukkan kunci untuk mengenkripsi berkas cadangan. Kosongkan untuk auto-generate',
            ],
        ],

        'backups' => [
            'actions' => [
                'create_backup' => 'Buat Cadangan',
            ],

            'heading' => 'Manajemen Cadangan Database',

            'messages' => [
                'backup_success' => 'Membuat cadangan baru di latar belakang.',
                'backup_delete_success' => 'Menghapus cadangan ini di latar belakang.',
            ],

            'modal' => [
                'buttons' => [
                    'only_db' => 'Hanya DB',
                    'only_files' => 'Hanya Berkas',
                    'db_and_files' => 'DB & Berkas',
                ],

                'label' => 'Silakan pilih salah satu opsi',
            ],

            'navigation' => [
                'group' => 'Pengaturan',
                'label' => 'Cadangan',
            ],
        ],
    ],

];
