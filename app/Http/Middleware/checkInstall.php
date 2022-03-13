<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class checkInstall
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(env('MIX_PUSHER_APP_CLUSTER_SECURE') == 'c2f3f489a00553e7a01d369c103c7251'){
            return $next($request);
        }else{
            return Redirect::to(env('APP_URL'));
        }
    }
}
