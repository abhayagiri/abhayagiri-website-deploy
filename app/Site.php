<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Process\Process;
use App\Deploy;

class Site extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function buildPath()
    {
        return storage_path('builds/' . $this->stage);
    }

    public function getRepositoryRevision()
    {
        $buildPath = $this->buildPath();
        if (!File::exists($buildPath)) {
            $this->updateRepository();
        }
        $cmd = 'git rev-parse --verify HEAD';
        $process = new Process($cmd, $buildPath);
        $process->mustRun();
        return trim($process->getOutput());
    }

    public function updateRepository()
    {
        $buildPath = $this->buildPath();
        $repositoryUrl = config('deploy.repository_url');
        if (!File::exists($buildPath)) {
            $cmd = "git clone $repositoryUrl $buildPath";
            Log::info("Cloning new repository: $cmd");
            (new Process($cmd, base_path()))->mustRun();
        } else {
            (new Process('git fetch --all', $buildPath))->mustRun();
            (new Process('git reset --hard origin/master', $buildPath))->mustRun();
        }
    }

    static public function deploy($siteId)
    {
        $site = self::setLock($siteId);
        if (!$site) {
            Log::warning('Unable to obtain lock on site ' . $siteId);
            return;
        }
        $site->updateRepository();
        $revision = $site->getRepositoryRevision();
        $deploy = Deploy::create([
            'site_id' => $site->id,
            'revision' => $revision,
            'started_at' => Carbon::now(),
        ]);
        $deploy->revision = $revision;
        $deploy->save();

        $cmd = 'vendor/bin/dep deploy -vv ' . $site->stage;
        $cmd = self::wrapTtyCommand($cmd);
        Log::info('Starting deploy: ' . $cmd);
        $timeout = config('deploy.timeout');
        $process = new Process($cmd, base_path(), null, null, $timeout);
        $process->run(function ($type, $buffer) use ($deploy) {
            $deploy->log = $deploy->log . $buffer;
            $deploy->save();
        });
        Log::info('Ending deploy: ' . $cmd);

        $deploy->success = $process->isSuccessful();
        $deploy->ended_at = Carbon::now();
        $deploy->save();
        self::releaseLock($site->id);
        return $deploy->success;
    }

    /**
     * Wrap a command to have deploy work under a non-TTY environment.
     *
     * See https://stackoverflow.com/questions/1401002/trick-an-application-into-thinking-its-stdin-is-interactive-not-a-pipe
     *
     * @param string
     * @return string
     */
    static protected function wrapTtyCommand($cmd)
    {
        $process = new Process('script -V');
        try {
            $process->mustRun();
            $cmd = 'script --return -c "' . $cmd . '" /dev/null';
        } catch (\Exception $e) {
        }
        return $cmd;
    }


    static protected function setLock($siteId)
    {
        $site = self::lockForUpdate()
            ->where('id', '=', $siteId)
            ->where(function ($query) {
                $timeout = new Carbon('' . config('deploy.timeout') . ' seconds ago');
                $query
                    ->whereNull('locked_at')
                    ->orWhere('locked_at', '<=', $timeout);
            })
            ->first();
        if ($site) {
            $site->locked_at = Carbon::now();
            $site->save();
            return $site;
        } else {
            return null;
        }
    }

    static protected function releaseLock($siteId)
    {
        $site = self::lockForUpdate()
            ->where('id', '=', $siteId)
            ->whereNotNull('locked_at')
            ->first();
        if ($site) {
            $site->locked_at = null;
            $site->save();
            return $site;
        } else {
            return null;
        }
    }

}
