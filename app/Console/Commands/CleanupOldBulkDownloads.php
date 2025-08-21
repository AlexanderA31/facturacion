<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BulkDownloadJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CleanupOldBulkDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-bulk-downloads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes old bulk download jobs and their associated zip files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old bulk download jobs...');

        $expirationDate = Carbon::now()->subDay();
        $deletedCount = 0;

        $oldJobs = BulkDownloadJob::where('created_at', '<', $expirationDate)->get();

        foreach ($oldJobs as $job) {
            if ($job->file_path && Storage::disk('public')->exists($job->file_path)) {
                Storage::disk('public')->delete($job->file_path);
                $this->line("Deleted zip file: {$job->file_path}");
            }
            $job->delete();
            $deletedCount++;
        }

        $this->info("Cleanup complete. Deleted {$deletedCount} old bulk download job(s).");
    }
}
