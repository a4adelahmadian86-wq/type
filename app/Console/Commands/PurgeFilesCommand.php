<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeFilesCommand extends Command
{
    protected $signature = 'farast:purge-files {--days=1 : Delete temporary files older than this many days}';
    protected $description = 'پاک‌سازی امن فایل‌های موقت FARAST';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $disk = Storage::disk('local');
        $prefixes = ['tmp', 'temporary', 'editor-tmp'];
        $deleted = 0;
        $cutoff = now()->subDays($days)->timestamp;

        foreach ($prefixes as $prefix) {
            foreach ($disk->allFiles($prefix) as $file) {
                try {
                    if ($disk->lastModified($file) < $cutoff) {
                        $disk->delete($file);
                        $deleted++;
                    }
                } catch (\Throwable $e) {
                    $this->warn("پاک‌سازی فایل {$file} انجام نشد.");
                }
            }
        }

        $this->info("{$deleted} فایل موقت قدیمی پاک شد.");
        return self::SUCCESS;
    }
}
