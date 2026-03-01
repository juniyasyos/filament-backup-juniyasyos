<x-filament-panels::page>
    <div class="flex flex-col gap-y-8">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-clock class="w-8 h-8 text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tugas Aktif</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->getActiveJobsCount() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-check-circle class="w-8 h-8 text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Berhasil</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->getCompletedJobsCount() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-x-circle class="w-8 h-8 text-red-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gagal</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->getFailedJobsCount() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-server-stack class="w-8 h-8 text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Penyimpanan</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ ucfirst(\Juniyasyos\FilamentLaravelBackup\Models\BackupSetting::get('backup.storage.default_disk', 'local')) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Backup Jobs Table --}}
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Tugas Cadangan
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Pantau dan kelola tugas cadangan Anda dengan pelacakan kemajuan real-time.
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        {{-- Reset All Button --}}
                        <button
                            wire:click="openResetAllModal"
                            class="inline-flex items-center px-4 py-2 border border-red-200 dark:border-red-700 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                            Reset Semua
                        </button>

                        {{-- Cleanup Button --}}
                        <button
                            wire:click="openCleanupModal"
                            class="inline-flex items-center px-4 py-2 border border-amber-200 dark:border-amber-700 shadow-sm text-sm leading-4 font-medium rounded-md text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-200">
                            <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                            Pembersihan
                        </button>

                        {{-- Refresh Button --}}
                        <button
                            wire:click="$refresh"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                            Segarkan
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>

        {{-- Backup Destination Status (if enabled) --}}
        @if($this->shouldDisplayStatusListRecords())
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Status Tujuan Cadangan
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Pantau kesehatan dan status tujuan cadangan Anda.
                </p>
            </div>

            <div class="p-6">
                @livewire(Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationStatusListRecords::class)
            </div>
        </div>
        @endif

        {{-- Existing Backups --}}
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    File Cadangan yang Ada
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Jelajahi dan kelola file cadangan yang ada dari tujuan penyimpanan Anda.
                </p>
            </div>

            <div class="p-6">
                @livewire(Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationListRecords::class)
            </div>
        </div>
    </div>

    {{-- Cleanup Modal --}}
    @if($cleanupModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('cleanupModalOpen') }" x-show="open">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-black/50 dark:bg-black/70" wire:click="closeCleanupModal"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Dialog --}}
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 px-6 py-4 border-b border-amber-200 dark:border-amber-700/50">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50">
                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Pembersihan Tugas Cadangan
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Hapus tugas cadangan lama dan file yang tidak diperlukan lagi
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-5 bg-white dark:bg-slate-800">
                    <div class="space-y-6">
                        {{-- Section: Kriteria Penghapusan --}}
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700/50">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-4 flex items-center">
                                <x-heroicon-o-cog-6-tooth class="w-4 h-4 mr-2" />
                                Pengaturan Pembersihan
                            </h4>

                            <div class="space-y-4">
                                {{-- Age Filter --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Hapus tugas yang lebih tua dari
                                    </label>
                                    <div class="flex items-center space-x-3">
                                        <input type="number" wire:model.number="cleanupOptions.older_than_days"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                                            min="1" max="365">
                                        <span class="px-3 py-2 bg-gray-100 dark:bg-slate-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Hari
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Hanya tugas yang dibuat lebih dari {{ $cleanupOptions['older_than_days'] }} hari yang lalu akan dihapus
                                    </p>
                                </div>

                                {{-- Keep Minimum --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Pertahankan jumlah minimum cadangan
                                    </label>
                                    <div class="flex items-center space-x-3">
                                        <input type="number" wire:model.number="cleanupOptions.keep_minimum"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                                            min="0" max="100">
                                        <span class="px-3 py-2 bg-gray-100 dark:bg-slate-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tugas
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Setidaknya {{ $cleanupOptions['keep_minimum'] }} tugas berhasil terbaru akan selalu disimpan
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Section: Opsi Penghapusan --}}
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-700/50">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-300 mb-4 flex items-center">
                                <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                                Kategori Penghapusan
                            </h4>

                            <div class="space-y-3">
                                {{-- Failed Jobs --}}
                                <label class="flex items-start p-3 border border-red-200 dark:border-red-700/50 rounded-lg hover:bg-red-100/50 dark:hover:bg-red-900/30 cursor-pointer transition-colors">
                                    <input type="checkbox" wire:model="cleanupOptions.cleanup_failed"
                                        class="m-1 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-500 focus:ring-red-500 dark:focus:ring-red-400">
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            Tugas yang Gagal
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            Hapus tugas cadangan yang gagal atau errror
                                        </p>
                                    </div>
                                </label>

                                {{-- Cancelled Jobs --}}
                                <label class="flex items-start p-3 border border-red-200 dark:border-red-700/50 rounded-lg hover:bg-red-100/50 dark:hover:bg-red-900/30 cursor-pointer transition-colors">
                                    <input type="checkbox" wire:model="cleanupOptions.cleanup_cancelled"
                                        class="m-1 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-500 focus:ring-red-500 dark:focus:ring-red-400">
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            Tugas yang Dibatalkan
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            Hapus tugas cadangan yang dibatalkan secara manual
                                        </p>
                                    </div>
                                </label>

                                {{-- Delete Files --}}
                                <label class="flex items-start p-3 border border-orange-300 dark:border-orange-700 rounded-lg hover:bg-orange-100/50 dark:hover:bg-orange-900/30 cursor-pointer transition-colors bg-orange-50/50 dark:bg-orange-900/10">
                                    <input type="checkbox" wire:model="cleanupOptions.cleanup_files"
                                        class="m-1 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-orange-600 dark:text-orange-500 focus:ring-orange-500 dark:focus:ring-orange-400">
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-orange-900 dark:text-orange-300 font-semibold">
                                            ⚠️ Hapus File dari Penyimpanan
                                        </p>
                                        <p class="text-xs text-orange-700 dark:text-orange-400">
                                            File cadangan akan dihapus permanen dari penyimpanan. Tindakan ini tidak dapat dibatalkan.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4 border border-gray-200 dark:border-slate-600">
                            <div class="flex items-start">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" />
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    <p class="font-medium mb-1">Informasi Penting:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>Pembersihan hanya menghapus data dari database jika tidak dipilih "Hapus File"</li>
                                        <li>Tugas terbaru yang berhasil selalu dipertahankan sesuai pengaturan</li>
                                        <li>Pastikan backup penting sudah dicopy sebelum pembersihan</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-slate-700/50 px-6 py-4 border-t border-gray-200 dark:border-slate-600 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0">
                    <button type="button" wire:click="closeCleanupModal"
                        class="w-full sm:w-auto px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm transition-colors duration-200">
                        Batalkan
                    </button>
                    <button type="button" wire:click="cleanupJobs"
                        class="w-full sm:w-auto px-6 py-2 rounded-lg bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-medium text-sm shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 dark:focus:ring-offset-slate-800 transition-all duration-200">
                        <span class="flex items-center justify-center">
                            <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                            Jalankan Pembersihan
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reset All Modal --}}
    @if($resetAllModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('resetAllModalOpen') }" x-show="open">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-black/60 dark:bg-black/80" wire:click="closeResetAllModal"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Dialog --}}
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30 px-6 py-4 border-b border-red-200 dark:border-red-700/50">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-red-100 dark:bg-red-900/50">
                                <x-heroicon-o-exclamation-circle class="h-7 w-7 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-red-900 dark:text-red-200">
                                Reset Semua Riwayat
                            </h3>
                            <p class="text-sm text-red-700 dark:text-red-400 mt-0.5">
                                Tindakan ini akan menghapus seluruh data tanpa filter
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-5 bg-white dark:bg-slate-800 space-y-5">
                    {{-- Warning Box --}}
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-4">
                        <div class="flex items-start">
                            <x-heroicon-o-shield-exclamation class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                            <div class="text-sm text-red-800 dark:text-red-300">
                                <p class="font-semibold mb-1">Perhatian! Ini adalah operasi berbahaya.</p>
                                <p class="text-xs leading-relaxed">
                                    Seluruh riwayat tugas cadangan akan <strong>dihapus permanen</strong> dari sistem, tanpa memandang status, tanggal, atau jenis cadangan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Delete Files Option --}}
                    <div class="border border-gray-200 dark:border-slate-600 rounded-lg overflow-hidden">
                        <label class="flex items-start p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" wire:model="resetAllDeleteFiles"
                                class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Sekaligus hapus file dari penyimpanan
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Jika dicentang, semua file ZIP cadangan di penyimpanan juga akan ikut dihapus secara permanen.
                                </p>
                            </div>
                        </label>
                    </div>

                    {{-- Confirm Text --}}
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4 border border-gray-200 dark:border-slate-600">
                        <div class="flex items-start">
                            <x-heroicon-o-information-circle class="w-4 h-4 text-gray-500 mt-0.5 mr-2 flex-shrink-0" />
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                Data yang telah dihapus <strong>tidak dapat dipulihkan</strong>. Pastikan Anda sudah mengunduh file cadangan yang dibutuhkan sebelum melanjutkan.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-slate-700/50 px-6 py-4 border-t border-gray-200 dark:border-slate-600 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0">
                    <button type="button" wire:click="closeResetAllModal"
                        class="w-full sm:w-auto px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm transition-colors duration-200">
                        Batal
                    </button>
                    <button type="button" wire:click="resetAllJobs"
                        class="w-full sm:w-auto px-6 py-2 rounded-lg bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-medium text-sm shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-slate-800 transition-all duration-200">
                        <span class="flex items-center justify-center">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 mr-2" />
                            Ya, Reset Semua
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>