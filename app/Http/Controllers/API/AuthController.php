<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Hash;
use Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function init()
    {   
        $user = Auth::user();
        return response()->json(['user' => $user], 200);   
    }

    public function login(Request $request)
    {   
        $rules = [
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
        ];

        $valid_fields = [
            'email' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        if(!Auth::attempt(['email' => $request->get('email'), 'password' => $request->get('password')], true))
        {   
            return response()->json(['error' => 'Invalid credentials'], 200);
        }

        $user = Auth::user();
        $user->last_login = Carbon::now()->toDateTimeString();
        $user->save();
        
        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        
        $user_roles = Auth::user()->roles->pluck('name')->all();

        $user_permissions = Auth::user()->getAllPermissions()->pluck('name');

        return response()->json([
            'user' => Auth::user(), 
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'user_permissions' => $user_permissions,
            'user_roles' => $user_roles,
        ], 200);

        // $accessToken->expires_at = Carbon::now()->addWeeks(1);
        // $accessToken->save();
        
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        
        return response()->json(['success' => 'You have been successfully logged out'], 200);
    }
}
