<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Services\Api\AuthService;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(Request $request)
    {
        try {
            if ($request->type == 'staff') {
                $request->validate([
                    'email' => 'required|email|unique:sale_staff,email',
                    'password' => 'required|string|min:8',
                ]);
            } elseif ($request->type == 'manager') {
                $request->validate([
                    'email' => 'required|email|unique:branch_managers,email',
                    'password' => 'required|string|min:8',
                ]);
            }  elseif ($request->type == 'asm') {
                $request->validate([
                    'email' => 'required|email|unique:area_sale_managers,email',
                    'password' => 'required|string|min:8',
                ]);
            } else {
                return response()->json(['message' => 'Invalid user type'], 422);
            }
            $user = $this->authService->register($request->all());
            return ResponseHelper::success($user, 'Registred successfully', null , 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'error', 422);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during registration', 'error', 500);
        }
    }
    public function login(Request $request)
    {
        try {
            // $request->validate([
            //     'email' => 'required|email',
            //     'password' => 'required|string',
            //     'type' => 'required|in:asm,staff,manager',
            // ]);
            $result = $this->authService->login($request->all());
            if (isset($result['error'])) {
               return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
               return ResponseHelper::success($result,'Login successful','200', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
               return ResponseHelper::error($e->errors(),'Validation failed','error', 422);
        } catch (\Exception $e) {
               return ResponseHelper::error($e->getMessage(),'Something went wrong','error', 500);
        }
    }

    public function sendOtp(Request $request)
    {
       try{
            //  $request->validate([
            //     'email' => 'required|email',
            //     'type' => 'required|in:customer,vendor',
            // ]);
            $data = $this->authService->sendOtp($request->all());
            return ResponseHelper::success($data, 'OTP send successfully to your email', '200', 200);
       }catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while sending OTP', 'error', 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            // $request->validate([
            //     'email' => 'required|email',
            //     'otp' => 'required|numeric',
            //     'type' => 'required|in:customer,vendor',
            // ]);
            $result = $this->authService->verifyOtp($request->all());
            if (isset($result['error'])) {
                return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
            return ResponseHelper::success($result, 'OTP verified successfully', '200', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'error', 422);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during OTP verification', 'error', 500);
        }
    }

    public function resendOtp(Request $request)
    {
        try {

            $data = $this->authService->resendOtp($request->all());

            return ResponseHelper::success($data,'OTP resent successfully to your email','200',200);

        } catch (\Exception $e) {

            return ResponseHelper::error($e->getMessage(),'An error occurred while resending OTP','error',500);

        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // $request->validate([
            //     'email' => 'required|email',
            //     'password' => 'required|string|min:8|confirmed',
            //     'type' => 'required|in:customer,vendor',
            // ]);
            $result = $this->authService->resetPassword($request->all());
            if (isset($result['error'])) {
                return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
            return ResponseHelper::success($result, 'Password reset successful', '200', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'error', 422);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during password reset', 'error', 500);
        }
    }
    public function logout(){
        try {
            $result = $this->authService->logout();
            if (isset($result['error'])) {
                return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
            return ResponseHelper::success(null, 'Logout successful', '200', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during logout', 'error', 500);
        }
    }
    public function getProfile(){
        try {
            $user = auth()->user();
            if (!$user) {
                return ResponseHelper::error(null, 'User not authenticated', 'error', 401);
            }
             $designation = \App\Models\Designation::find($user->designation_id);

            $user->designation_name = $designation->name ?? null;

             if ($user instanceof \App\Models\BranchManager || $user instanceof \App\Models\SaleStaff) {

                $branch = \App\Models\Branch::find($user->branch_id);

                $user->branch_name = $branch->name ?? null;

                unset($user->branch_id);
            }

            // ASM → Region Name
            if ($user instanceof \App\Models\AreaSaleManager) {

                $region = \App\Models\Region::find($user->region_id);

                $user->region_name = $region->name ?? null;

                unset($user->region_id);
            }

            unset($user->designation_id);
            return ResponseHelper::success($user, 'Profile retrieved successfully', '200', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving profile', 'error', 500);
        }
    }

    public function updateProfile(Request $request){
        try {
            if($request->type == 'customer') {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email,' . auth()->id(),
                    'phone' => 'nullable|string|max:15',
                    'type' => 'required|in:customer,vendor',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);
            } elseif ($request->type == 'vendor') {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:vendors,email,' . auth()->id(),
                    'phone' => 'nullable|string|max:15',
                    'type' => 'required|in:customer,vendor',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);
            }
            $result = $this->authService->updateProfile($request->all());
            if (isset($result['error'])) {
                return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
            return ResponseHelper::success($result, 'Profile updated successfully', '200', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'error', 422);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during profile update', 'error', 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $result = $this->authService->changePassword($request->all());
            if (isset($result['error'])) {
                return ResponseHelper::error(null, $result['error'], 'error', 401);
            }
            return ResponseHelper::success($result, 'Password changed successfully', '200', 200);
        }  catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred during password change', 'error', 500);
        }
    }


}