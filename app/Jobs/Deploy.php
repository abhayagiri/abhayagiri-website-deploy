<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Deploy as DeployModel;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
        DeployModel::run($this->siteId);
    }
}
