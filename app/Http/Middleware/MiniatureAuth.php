<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Hash;
use App\User;

class MiniatureAuth
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
        // TODO checking the authentication on cookies
        $flag = false;
        if($request->input('username') != null){
            $user = User::where('username' , $request->input('username'))->first();
            $flag = Hash::check($request->input('password'), $user->password);
        }else{
            $user = User::where('username' , $request->route('username'))->first();
            $flag = Hash::check($request->route('password'), $user->password);
        }
        if($flag){
            return $next($request);
        }else{
            return response()->json([
                'status' => 401 ,
                'msg' => "logging in required"
            ]);
        }
    }
}
