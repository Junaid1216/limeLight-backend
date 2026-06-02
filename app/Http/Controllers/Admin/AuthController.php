<?php

namespace App\Http\Controllers\Admin;

use Hash;
use App\Models\SubAdmin;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    public function getLoginPage()
    {
        return view('admin.auth.login');
    }
    public function Login(Request $request)
    {

        
        // return $request->password;
        $request->validate([
            'email' => 'required',
            'password' => 'required',
                //    'g-recaptcha-response' => 'required',
        ]);
       

    // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
    //     'secret' => config('services.recaptcha.secret_key'),
    //     'response' => $request->input('g-recaptcha-response'),
    //     'remoteip' => $request->ip(),
    // ]);

    // $responseData = $response->json();

    // if (!($responseData['success'] ?? false)) {
    //     return back()->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.']);
    // }
        $remember_me = ($request->remember_me) ? true : false;

        if (Auth()->guard('admin')->attempt(
            [
                'email' => $request->email,
                'password' => $request->password
            ],
            $remember_me
        )) {
            return redirect('admin/dashboard')->with('success', 'Logged In Successfully!');
        }

        $subAdmin = SubAdmin::where('email', $request->email)->first();
        

        if ($subAdmin && Hash::check($request->password, $subAdmin->password)) {
           

           if ($subAdmin->status == 1) {
                auth()->guard('subadmin')->login($subAdmin, $remember_me);
                
                return redirect('admin/dashboard')->with('success', 'Sub-Admin Login Successfully!');
            } else {
                // Check if user is already logged in
                
                
                return redirect('admin/')->with('error', 'Your account is deactivated. Please contact the admin.');
            }
        }

       

        return back()->with('error', 'Invalid email or password');
    }
}
