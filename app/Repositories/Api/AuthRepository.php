<?php

namespace App\Repositories\Api;

use App\Models\User;
use App\Models\Vendor;
use App\Mail\ForgotOTPMail;
use App\Models\VendorImage;
use App\Mail\UserCredentials;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Repositories\Api\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $request)
    {
        if ($request['type'] == 'customer') {
            $customer = User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'password' => Hash::make($request['password']),
            ]);

            // Mail::to($customer->email)->send(new UserCredentials(
            //     $customer->name ?? 'User',
            //     $customer->email,
            //     $customer->phone ?? ''
            // ));
            return $customer;
        } else if ($request['type'] == 'vendor') {

        $cnicFrontPath = null;
        if (!empty($request['cnic_front'])) {
            $cnicFront = $request['cnic_front'];
            $filename = time() . '_cnic_front_' . uniqid() . '.' . $cnicFront->getClientOriginalExtension();
            $cnicFront->move(public_path('admin/assets/images/users/'), $filename);
            $cnicFrontPath = 'public/admin/assets/images/users/' . $filename;
        }

        // ✅ Handle CNIC Back
        $cnicBackPath = null;
        if (!empty($request['cnic_back'])) {
            $cnicBack = $request['cnic_back'];
            $filename = time() . '_cnic_back_' . uniqid() . '.' . $cnicBack->getClientOriginalExtension();
            $cnicBack->move(public_path('admin/assets/images/users/'), $filename);
            $cnicBackPath = 'public/admin/assets/images/users/' . $filename;
        }

        
            $vendor = Vendor::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'location'   => $request['location'] ?? null,
                'password' => Hash::make($request['password']),
                'cnic_front' => $cnicFrontPath,
                'cnic_back'  => $cnicBackPath,
                
            ]);
            // Mail::to($vendor->email)->send(new UserCredentials(
            //     $vendor->name ?? 'Vendor',
            //     $vendor->email,
            //     $vendor->phone ?? ''
            // ));
           if (!empty($request['image'])) {
            foreach ($request['image'] as $file) {
                $filename = time() . '_vendor_img_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('admin/assets/images/users/'), $filename);
                $imagePath = 'public/admin/assets/images/users/' . $filename;

                VendorImage::create([
                    'vendor_id' => $vendor->id,
                    'image'     => $imagePath,
                ]);
            }
        }

        return $vendor->load('images');
        }
    }
    public function login(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }
        if (!$user) {
            return ['error' => ucfirst($request['type']) . ' not found'];
        }
        if ((isset($user->status) && $user->status == 0)) {
            return ['error' => 'Your account has been deactivated.'];
        }
        if (!Hash::check($request['password'], $user->password)) {
            return ['error' => 'Invalid credentials'];
        }
        $token = $user->createToken($request['type'] . '_token')->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    public function sendOtp(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        }
        if (!$user) {
            return ['error' => ucfirst($request['type']) . ' not found'];
        }
        $otp = rand(1000, 9999);
        $user->otp = $otp;
        $user->save();
        Mail::to($user->email)->send(new ForgotOTPMail($otp));
        return [
            'otp' => $otp,
            'email' => $user->email
        ];
    }
    public function verifyOtp(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }
        if (!$user || $user->otp !== $request['otp']) {
            return ['error' => 'Invalid OTP'];
        }
        $user->otp = null;
        $user->save();
        return ['email' => $user->email];
    }
    public function resetPassword(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }
        if (Hash::check($request['password'], $user->password)) {
            return ['error' => 'New password cannot be the same as the old password'];
        }
        $user->password = Hash::make($request['password']);
        $user->save();
        return $user;
    }

    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $user->tokens()->delete();
            return true;
        }
        return ['error' => 'User not authenticated'];
    }
    public function updateProfile(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }
        if (!$user) {
            return ['error' => ucfirst($request['type']) . ' not found'];
        }
        $user->name = $request['name'] ?? $user->name;
        $user->phone = $request['phone'] ?? $user->phone;
        $user->email = $request['email'] ?? $user->email;
        if (isset($request['image']) && $request['image'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $request['image'];
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move(public_path('admin/assets/images/users'), $filename);
            $user->image = 'public/admin/assets/images/users/' . $filename;
        }

        $user->save();
    }
    public function changePassword(array $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ['error' => 'User not authenticated'];
        }
       if ($request['new_password'] !== $request['confirm_password']) {
        return ['error' => 'New password and confirm password do not match'];
    }
    if (Hash::check($request['new_password'], $user->password)) {
        return ['error' => 'New password cannot be the same as the old password'];
    }
        $user->password = Hash::make($request['new_password']);
        $user->save();
        return $user;
    }
}
