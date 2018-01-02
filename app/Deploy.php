<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Site;

class Deploy extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function site()
    {
        return $this->belongsTo('App\Site');
    }

    static public function run($siteId)
    {
        $site = Site::setLock($siteId);
        if (!$site) {
            Log::warning('Unable to obtain lock on site ' . $siteId);
            return;
        }
        $site->updateRepository();
        $revision = $site->getRepositoryRevision();
        $deploy = self::create([
            'site_id' => $site->id,
            'revision' => $revision,
            'started_at' => Carbon::now(),
        ]);
        $deploy->revision = $revision;
        $deploy->save();

        $cmd = 'vendor/bin/dep deploy ' . $site->stage;
        $timeout = config('deploy.timeout');
        Log::info('Starting deploy: ' . $cmd);
        $log = '';
        $process = new Process($cmd, base_path(), null, null, $timeout);
        $process->run(function ($type, $buffer) use ($deploy) {
            $deploy->log = $deploy->log . $buffer;
            $deploy->save();
        });
        Log::info('Ending deploy: ' . $cmd);

        $deploy->success = $process->isSuccessful();
        $deploy->ended_at = Carbon::now();
        $deploy->save();
        Site::releaseLock($site->id);
        return $deploy->success;
    }

}
