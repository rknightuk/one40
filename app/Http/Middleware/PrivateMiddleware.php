<?php

namespace App\Http\Middleware;

use Closure;

class PrivateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
	    $user = $request->user();

    	if (env('PRIVATE') && ! $user) return redirect('login');

        return $next($request);
    }
}
