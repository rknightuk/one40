<?php

namespace App\Http\Controllers;

use App\Fetcher\FetchLog;

class LogController extends Controller
{
    public function index()
    {
    	$logs = FetchLog::orderBy('created_at', 'desc')->paginate(30);

	    return view('admin.logs', compact(
		    'logs'
	    ));
    }

	public function purge()
	{
		$logs = FetchLog::all();

		$ids = $logs->pluck('id');

		FetchLog::destroy($ids->toArray());

		return redirect('logs');
	}
}
