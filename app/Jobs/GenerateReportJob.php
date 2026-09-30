<?php

namespace App\Jobs;

use App\Models\System\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 60];
    public int $timeout = 300;

    public function __construct(
        public int $reportId
    ) {}

    public function handle(): void
    {
        $report = Report::find($this->reportId);
        if (!$report) {
            return;
        }

        $report->update([
            'status' => 'completed',
            'result_data' => [
                'summary' => 'Asynchronous report compilation completed.',
                'completed_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
