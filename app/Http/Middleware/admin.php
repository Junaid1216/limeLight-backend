<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AuthController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class admin
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

        if(auth::guard('admin')->check() || auth::guard('subadmin')->check()){

            return $next($request);
        }else{

        return redirect('admin');
     }



    }
}
