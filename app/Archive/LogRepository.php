<?php

namespace App\Archive;

class LogRepository {

	public function all()
	{
		return Log::all();
	}

	public function markImported($filename)
	{
		return Log::create(['filename' => $filename]);
	}

}