<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\DeploySite;
use App\Deploy;
use App\Site;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', [
            'except' => ['githubWebhook']
        ]);
    }

    /**
     * Show all the deployments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $minDate = new Carbon('' . config('deploy.max_days') . ' days ago');
        $deploys = Deploy::where('started_at', '>=', $minDate)
            ->orderBy('started_at', 'desc')->get();
        $sites = Site::orderBy('name')->get();
        return view('home', [
            'deploys' => $deploys,
            'sites' => $sites,
        ]);
    }

    /**
     * Show a single deployment.
     *
     * @return \Illuminate\Http\Response
     */
    public function showDeploy(Request $request, $id)
    {
        $deploy = Deploy::findOrFail($id);
        return view('deploy', ['deploy' => $deploy]);
    }

    /**
     * Start deployment.
     *
     * @return \Illuminate\Http\Response
     */
    public function startDeploy(Request $request, $id)
    {
        $site = Site::findOrFail($id);
        DeploySite::dispatch($site->id);
        $message = 'Starting ' . $site->name . ' deployment';
        return redirect(route('home'))->with('status', $message);
    }

    public function githubWebhook(Request $request)
    {
        $site = Site::where('stage', 'staging')->firstOrFail();
        // Courtesy of https://gist.github.com/joemaller/e5e0b737a321d69ae2fc
        $signature = $request->header('X-Hub-Signature', '');
        $payload = '' . $request->getContent();
        $secret = env('DEPLOYER_SECRET');
        $testSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);
        $result = hash_equals($signature, $testSignature);
        if ($result) {
            DeploySite::dispatch($site->id);
            return 'OK';
        } else {
            abort(500);
        }
    }
}
