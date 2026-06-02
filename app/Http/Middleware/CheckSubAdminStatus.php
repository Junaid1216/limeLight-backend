<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubAdminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
         if (Auth::guard('subadmin')->check()) {
            $subAdmin = Auth::guard('subadmin')->user();

            // Check if subadmin is deactivated
            if ($subAdmin->status == 0) {
                Auth::guard('subadmin')->logout();

                return redirect('admin/')->with('error', 'Your account is deactivated. Please contact the admin.');
            }
        }

        return $next($request);
    
    }
}
