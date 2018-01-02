<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Deploy;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show all the deployments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $deploys = Deploy::orderBy('started_at', 'desc')->get();
        return view('home', ['deploys' => $deploys]);
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
}
