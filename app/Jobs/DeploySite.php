<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Site;

class DeploySite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60; // 1 hour

    /**
     * The site ID.
     *
     * @var int
     */
    protected $siteId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Site::deploy($this->siteId);
    }
}
