<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\JobNotification;
use App\Mail\UserEmailOtp;
use App\Models\EmailOtp;
use App\Models\User;
use App\Models\UserWallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;

class EmailOtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        try {
            $data = $request->only('email', 'phone', 'country');

            $rules = [];
            $messages = [];

            if (!empty($data['email'])) {
                $rules['email'] = [
                    'unique:users,email',
                ];
                $messages['email.unique'] = 'This email is already taken';
            }

            if (!empty($data['phone'])) {
                $rules['phone'] = [
                    'unique:users,phone',
                ];
                $messages['phone.unique'] = 'This phone number is already taken';
            }

            $request->validate($rules, $messages);

            $otp = rand(1000, 9999);
            $otpToken = Str::uuid();

            $condition = [];

            if (!empty($request->email)) {
                $condition['email'] = $request->email;
            } else {
                $condition['phone'] = $request->phone;
            }

            EmailOtp::updateOrCreate(
                $condition,
                [
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'country' => $request->country,
                    'otp' => $otp,
                    'otp_token' => $otpToken,
                ]
            );

            if (!empty($request->email)) {
                Mail::to($request->email)->send(new UserEmailOtp($otp));
            } else {
                // Here you can implement sending OTP via SMS if needed
                $phone = $request->phone;
                $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
                $twilio->messages->create($phone, [
                    'from' => env('TWILIO_PHONE_NUMBER'),
                    'body' => "Dear user, your One-Time Password (OTP) is $otp. Please do not share this code with anyone. - RenSolutions",
                ]);
            }

            return response()->json([
                'message' => !empty($request->email)
                    ? 'A verification OTP has been sent to your email'
                    : 'A verification OTP has been sent to your phone',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->all();

        try {
            // OTP base query
            $query = EmailOtp::where('otp', $request->otp);

            if (!empty($data['email'])) {
                $query->where('email', $data['email']);
            }

            if (!empty($data['phone'])) {
                $query->where('phone', $data['phone']);
            }

            $otpRecord = $query->latest()->first();

            if (! $otpRecord) {
                return response()->json([
                    'message' => 'Invalid OTP',
                ], 400);
            }

            return response()->json([
                'message' => 'OTP verified successfully',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerUser(Request $request)
    {
        try {
            $request->validate(
                [
                    'email' => 'nullable|unique:users,email',
                    'phone' => 'nullable|unique:users,phone',
                    'password' => 'required|min:6',
                ],
                [
                    'email.unique' => 'This email is already taken.',
                    'phone.unique' => 'This phone number is already taken.',
                ]
            );

            // OTP Record Check
            $otpRecord = EmailOtp::where('email', $request->email)->first();
            if (! $otpRecord) {
                return response()->json([
                    'error' => 'OTP record not found for the given email.',
                ], 404);
            }

            // ✅ User Create
            $user = User::create([
                'email' => $otpRecord->email,
                'phone' => $otpRecord->phone,
                'country' => $otpRecord->country,
                'password' => Hash::make($request->password),
                'status' => is_null($otpRecord->phone) ? 1 : (is_null($otpRecord->email) ? 2 : null),
            ]);

            // ✅ Signup reward points read karo
            $rewardPoints = \App\Models\SignupRewardSetting::first();
            $points = $rewardPoints ? $rewardPoints->points : 0;

            // ✅ User wallet me insert karo
            \App\Models\UserWallet::create([
                'user_id' => $user->id,
                'total_points' => $points,
            ]);

            // ✅ Delete OTP record
            $otpRecord->delete();

            // ✅ Notification send (Push + DB)
            // Title & Description
            $title = 'Signup Reward Points';
            $description = "Welcome {$user->email}, you’ve earned {$points} points for signing up!";

            // Extra Data for Push Notification
            $data = [
                'type' => 'signup_points',
                'points' => $points,
            ];

            // Push Notification (only if fcm_token exists)
            if ($user && $user->fcm) {
                // Dispatch job to send push notification
                dispatch(new JobNotification(
                    $user->fcm,
                    $title,
                    $description,
                    $data
                ));
            }

            // Save Notification in Database
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'description' => $description,
                'seenByUser' => 0, // unseen by default
            ]);

            return response()->json([
                'message' => 'Registered successfully.',
                'user_id' => $user->id,
                'assigned_points' => $points,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getLoggedInUserInfo()
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $totalPoints = UserWallet::with('user')
                ->where('user_id', $user->id)
                ->first();

            return response()->json([
                'name' => $user->name ?? null,
                'image' => $user->image ? 'public/'.$user->image : 'https://ranglerzwp.xyz/myren/public/admin/assets/images/avator.png',
                'country' => $user->country ?? null,
                'email' => $user->email ?? null,
                'phone' => $user->phone ?? null,
                'points' => $totalPoints ?? 0,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something Went Wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

 
     public function requestUpdateOtp(Request $request)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // ======= ✅ CUSTOM VALIDATION (Unique Email/Phone) =======
            try {
                $request->validate([
                    'email' => [
                        'nullable',
                        'email',
                        Rule::unique('users', 'email')->ignore($user->id),
                    ],
                    'phone' => [
                        'nullable',
                        Rule::unique('users', 'phone')->ignore($user->id),
                    ],
                ], [
                    'email.unique' => 'This email is already taken by another user.',
                    'phone.unique' => 'This phone number is already taken by another user.',
                ]);
            } catch (ValidationException $e) {
                $errors = $e->validator->errors()->first();

                return response()->json(['message' => $errors], 422);
            }

            // ======= DATA READY =======
            $data = $request->only('email', 'phone', 'name', 'image');
            $status = (string) $user->status;
            $updatedFields = [];

            // ✅ Name
            if (!empty($data['name'])) {
                $updatedFields['name'] = $data['name'];
            }

            // ✅ Image
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
                $imagePath = 'admin/assets/images/users/';
                $image->move(public_path($imagePath), $imageName);
                $updatedFields['image'] = $imagePath.$imageName;
            }

            $otp = null;
            $otpToken = null;
            $sendOtpTo = null;

            $pendingData = [
                'email' => $user->email,
                'phone' => $user->phone,
                'name' => $data['name'] ?? null,
                'image' => $updatedFields['image'] ?? null,
                'country' => $user->country ?? 'N/A',
            ];

			

            // ======= STATUS HANDLING =======
            if ($status === '1') {
               if (isset($data['email']) && empty($updatedFields) && empty($data['phone'])) {

                    return response()->json(['message' => "You can't update your email"], 200);
                }

				if (isset($data['phone']) && trim($data['phone']) === trim($user->phone)) {

					if (isset($data['name']) && trim($data['name']) !== '') {
						$user->name = $data['name'];
					}

					if (isset($data['image']) && trim($data['image']) !== '') {
						$user->image = $data['image'];
					}

					$user->save(); // changes persist in DB

					return response()->json([
						'message' => "Profile updated successfully."
					]);
				}
                // email locked
                if (!empty($data['phone']) && $data['phone'] !== $user->phone) {
                    $otp = rand(1000, 9999);
                    $otpToken = Str::uuid();
                    $pendingData['phone'] = $data['phone'];
                    $sendOtpTo = 'phone';
                }

                if (empty($data['phone']) && !empty($updatedFields)) {
                    $user->name = $updatedFields['name'] ?? $user->name;
                    $user->image = $updatedFields['image'] ?? $user->image;
                    $user->save();

                    return response()->json(['message' => 'Profile updated successfully.'], 200);
                }
            } elseif ($status === '2') { // phone locked
                 if (isset($data['phone']) && empty($updatedFields) && empty($data['email'])) {
                    return response()->json(['message' => "You can't update your phone"], 200);
                }
                if (!empty($data['email']) && $data['email'] !== $user->email) {
                    $otp = rand(1000, 9999);
                    $otpToken = Str::uuid();
                    $pendingData['email'] = $data['email'];
                    $sendOtpTo = 'email';
                }

                if (empty($data['email']) && !empty($updatedFields)) {
                    $user->name = $updatedFields['name'] ?? $user->name;
                    $user->image = $updatedFields['image'] ?? $user->image;
                    $user->save();

                    return response()->json(['message' => 'Profile updated successfully.'], 200);
                }
            } else { // no restriction

				
                if (!empty($data['email'])) {
                    $user->email = $data['email'];
                }
                if (!empty($data['phone'])) {
                    $user->phone = $data['phone'];
                }
                if (!empty($updatedFields['name'])) {
                    $user->name = $updatedFields['name'];
                }
                if (!empty($updatedFields['image'])) {
                    $user->image = $updatedFields['image'];
                }
                $user->save();

                return response()->json(['message' => 'Profile updated successfully.'], 200);
            }

            // ======= CREATE OTP IF NEEDED =======
            if ($otp && $otpToken) {
                $pendingData['otp'] = $otp;
                $pendingData['otp_token'] = $otpToken;

                EmailOtp::create($pendingData);

                if ($sendOtpTo === 'phone') {
                    $phone = $pendingData['phone'];
                    if (substr($phone, 0, 1) !== '+') {
                        $phone = '+'.$phone;
                    }
                    try {
                        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
                        $twilio->messages->create($phone, [
                            'from' => env('TWILIO_PHONE_NUMBER'),
                            'body' => "Your OTP is $otp",
                        ]);
                    } catch (\Exception $e) {
                        return response()->json(['error' => 'Twilio failed', 'message' => $e->getMessage()], 500);
                    }
                    $msg = 'A verification OTP has been sent to your phone.';
                } else {
                    Mail::to($pendingData['email'])->send(new UserEmailOtp($otp));
                    $msg = 'A verification OTP has been sent to your email.';
                }

                return response()->json(['message' => $msg, 'otp_token' => $otpToken], 200);
            }

            if (!empty($updatedFields)) {
                if (!empty($updatedFields['image'])) {
                    $user->image = $updatedFields['image'];
                }
                if (!empty($updatedFields['name'])) {
                    $user->name = $updatedFields['name'];
                }

                $user->save();

                return response()->json([
                    'message' => 'Profile updated successfully.',
                ], 200);

            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyAndUpdateContact(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }


			 try {
                $request->validate([
                    'email' => [
                        'nullable',
                        'email',
                        Rule::unique('users', 'email')->ignore($user->id),
                    ],
                    'phone' => [
                        'nullable',
                        Rule::unique('users', 'phone')->ignore($user->id),
                    ],
                ], [
                    'email.unique' => 'This email is already taken by another user.',
                    'phone.unique' => 'This phone number is already taken by another user.',
                ]);
            } catch (ValidationException $e) {
                $errors = $e->validator->errors()->first();

                return response()->json(['message' => $errors], 422);
            }

            $otpRecord = EmailOtp::where('otp', $request->otp)->first();

            if (! $otpRecord) {
                return response()->json(['error' => 'Invalid Otp'], 400);
            }

            $updated = false;

            if (!empty($otpRecord->email) && $otpRecord->email !== $user->email) {
                $user->email = $otpRecord->email;
                $updated = true;
            }

            if (!empty($otpRecord->email) && $otpRecord->phone !== $user->phone) {
                $user->phone = $otpRecord->phone;
                $updated = true;
            }

            if (!empty($otpRecord->name)) {
                $user->name = $otpRecord->name;
                $updated = true;
            }

            if (!empty($otpRecord->image)) {
                $user->image = $otpRecord->image;
                $updated = true;
            }

            if (! $updated) {
                return response()->json(['error' => 'Nothing to update'], 422);
            }

            $user->save();
            $otpRecord->delete();

            return response()->json(['message' => 'Profile updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong'.$e->getMessage()], 500);
        }
    }
}
