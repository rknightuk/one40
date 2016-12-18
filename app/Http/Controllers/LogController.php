<?php

namespace App\Http\Controllers;

use App\FetchLog;

class LogController extends Controller
{
    public function index()
    {
    	$logs = FetchLog::orderBy('created_at', 'desc')->paginate(30);

	    return view('admin.logs', compact(
		    'logs'
	    ));
    }
}
