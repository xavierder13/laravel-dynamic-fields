<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class ActivityLogs
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
        $user = Auth::user();

        //Activity Logs
        if($request->is('api/activity_logs/index')){
            if($user->can('activity-logs')){
                return $next($request); 
            }
        }

        return abort(401, 'Unauthorized');
    }
}
