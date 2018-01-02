<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Process\Process;

class Site extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function buildPath()
    {
        return storage_path('builds/' . $this->id);
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

    static public function setLock($siteId)
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

    static public function releaseLock($siteId)
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
