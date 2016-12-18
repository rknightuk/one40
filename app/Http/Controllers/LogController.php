<?php

namespace App\Http\Controllers;

use App\FetchLog;

class LogController extends Controller
{
    public function index()
    {
    	$logs = FetchLog::all();

	    return view('admin.logs', compact(
		    'logs'
	    ));
    }
}
