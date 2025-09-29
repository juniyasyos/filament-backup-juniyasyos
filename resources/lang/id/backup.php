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
        'backups' => [
            'actions' => [
                'create_backup' => 'Buat Cadangan',
            ],

            'heading' => 'Cadangan',

            'messages' => [
                'backup_success' => 'Membuat cadangan baru di latar belakang.',
                'backup_delete_success' => 'Menghapus cadangan ini di latar belakang.',
            ],

            'form' => [
                'option' => [
                    'label' => 'Opsi Cadangan',
                    'all' => 'Semua (Basis Data + Berkas)',
                    'only_db' => 'Hanya Basis Data',
                    'only_files' => 'Hanya Berkas',
                ],
            ],

            'modal' => [
                'heading' => 'Pilih Jenis Cadangan',
                'submit' => 'Jalankan Cadangan',
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

        'settings' => [
            'heading' => 'Pengaturan Cadangan',

            'navigation' => [
                'label' => 'Pengaturan Cadangan',
            ],

            'sections' => [
                'general' => 'Umum',
                'security' => 'Keamanan',
                'queue_and_notifications' => 'Antrean & Notifikasi',
                'scheduling' => 'Penjadwalan',
                'retention' => 'Retensi',
                'scopes' => 'Cakupan',
                'advanced' => 'Lanjutan',
            ],

            'fields' => [
                'enabled' => 'Aktif',
                'allow_manual_runs' => 'Izinkan Eksekusi Manual',
                'require_password' => 'Wajib Kata Sandi',
                'new_password' => 'Kata Sandi Baru',
                'encrypt_backups' => 'Enkripsi Cadangan',
                'encryption_password' => 'Kata Sandi Enkripsi',
                'use_queue' => 'Gunakan Antrean',
                'queue' => 'Nama Antrean',
                'notification_channel' => 'Kanal Notifikasi',
                'notification_targets' => 'Tujuan Notifikasi',
                'scheduled' => 'Aktifkan Jadwal',
                'schedule_cron' => 'Jadwal CRON',
                'retention_days' => 'Retensi (Hari)',
                'retention_copies' => 'Retensi (Salinan)',
                'allowed_disks' => 'Disk yang Diizinkan',
                'options' => 'Opsi (key => value)',
            ],

            'helper_texts' => [
                'password' => 'Kosongkan bila tidak ingin mengganti.',
                'encryption_password' => 'Masukkan frasa sandi untuk mengenkripsi arsip cadangan.',
                'queue' => 'Kosongkan untuk memakai antrean bawaan.',
                'schedule_cron' => 'Format CRON. Contoh: "0 3 * * *" untuk jam 03:00 setiap hari.',
            ],

            'placeholders' => [
                'notification_channel' => 'mail, slack, database, dll.',
                'notification_targets' => 'email/username/channel',
                'allowed_disks' => 'ketik nama disk...',
            ],

            'actions' => [
                'save' => [
                    'label' => 'Simpan pengaturan',
                ],
                'add_option' => 'Tambah Opsi',
            ],

            'descriptions' => [
                'general' => 'Atur perilaku dasar dan pembatasan akses sebelum menjalankan cadangan.',
                'security' => 'Lindungi cadangan dengan kata sandi dan enkripsi arsip opsional.',
                'queue_and_notifications' => 'Kendalikan antrean proses cadangan serta penerima notifikasi.',
                'scheduling' => 'Otomatisasi eksekusi cadangan dengan ekspresi CRON.',
                'retention' => 'Batasi lamanya cadangan disimpan untuk menghemat ruang.',
                'scopes' => 'Batasi disk penyimpanan yang ditampilkan di halaman cadangan.',
                'advanced' => 'Sesuaikan opsi lanjutan yang diteruskan ke perintah cadangan.',
            ],

            'notifications' => [
                'saved' => [
                    'title' => 'Pengaturan disimpan',
                    'body' => 'Konfigurasi cadangan berhasil diperbarui.',
                ],
                'password_required' => [
                    'title' => 'Kata sandi wajib diisi',
                    'body' => 'Masukkan kata sandi untuk mengaktifkan perlindungan kata sandi.',
                ],
                'encryption_password_required' => [
                    'title' => 'Kata sandi enkripsi wajib diisi',
                    'body' => 'Masukkan kata sandi enkripsi untuk mengaktifkan enkripsi.',
                ],
            ],
        ],
    ],

];
