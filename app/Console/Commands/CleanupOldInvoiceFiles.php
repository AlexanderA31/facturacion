<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldInvoiceFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:cleanup {--days=7 : The number of days to keep files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old signed XML and temporary PDF files from public storage.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting cleanup of old invoice files...');

        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('The --days option must be a positive integer.');
            return Command::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days);
        $this->info("Deleting files older than {$days} days (before {$cutoff->toDateTimeString()})...");

        $directories = [
            'comprobantes/firmados',
            'temp',
        ];

        $deletedCount = 0;

        foreach ($directories as $directory) {
            $files = Storage::disk('public')->files($directory);

            foreach ($files as $file) {
                if (Storage::disk('public')->lastModified($file) < $cutoff->getTimestamp()) {
                    Storage::disk('public')->delete($file);
                    $deletedCount++;
                    $this->line("Deleted: {$file}");
                }
            }
        }

        $this->info("Cleanup complete. Deleted {$deletedCount} files.");
        return Command::SUCCESS;
    }
}
