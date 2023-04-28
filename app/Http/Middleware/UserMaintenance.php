<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class UserMaintenance
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

        //User Record
        if($request->is('api/user/index')){
            if($user->can('user-list')){
                return $next($request); 
            }
        }

        //User Create
        if($request->is('api/user/create') || $request->is('api/user/store')){
            if($user->can('user-create')){
                return $next($request); 
            }
        }

        //User Edit
        if($request->is('api/user/edit/*') || $request->is('api/user/update/*')){
            if($user->can('user-edit')){
                return $next($request); 
            }
        }

        //User Profile Edit
        if($request->is('api/user/update_profile/*')){  
            return $next($request);  
        }

        //User Delete
        if($request->is('api/user/delete')){
            if($user->can('user-delete')){
                return $next($request); 
            }
        }

        //User Roles Permissions
        if($request->is('api/user/roles_permissions')){
            return $next($request); 
        }

        return abort(401, 'Unauthorized');

    }
}
