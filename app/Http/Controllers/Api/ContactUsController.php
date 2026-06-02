<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Models\Getcontactus;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\ContactUsMail; 


class ContactUsController extends Controller
{
    // Store contact data
    public function contact(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => [
                    'unique:contact_us,email',
                ],
                'phone' => 'unique:contact_us,phone',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors()->all(),
                ], 422);
            }
            $contact = ContactUs::find($id);

            if ($contact) {
                $contact->email = $request->email;
                $contact->phone = $request->phone;
                $contact->save();
            }

            return response()->json([

                'message' => 'Contact Us Updated Successfully',
                'data' => $contact,
            ], 200);

        } catch (Exception $e) {
            return response()->json([

                'message' => 'Something Went Wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //  Get contact
    public function getContact($id)
    {
        try {
            $contact = ContactUs::select('email', 'phone')->find($id);

            if (! $contact) {
                return response()->json([

                    'message' => 'No Contact Found',
                ], 404);
            }

            return response()->json([

                'message' => 'Contact Found Successfully',
                'data' => $contact,
            ], 200);

        } catch (Exception $e) {
            return response()->json([

                'message' => 'Unable To Fetch Contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getContactUsDetails()
    {
        $contact = ContactUs::select('email', 'phone')->first();

        return response()->json([
            'message' => 'Contact Us details retrieved successfully',
            'data' => $contact,
        ]);
    }

	
	public function Submitcontact(Request $request){
		return $request;
    $contact = Getcontactus::create([
        'email' => $request->email,
        'message' => $request->message,
    ]);

    $adminEmail = ContactUs::value('email');
    Mail::to($adminEmail)->send(new ContactUsMail($contact->email, $contact->message));
    return response()->json([
        // 'status' => true,
        'message' => 'Your message has been sent successfully',
        'data' => $contact
    ],200);
    }

public function contactUs() 
{
    $data = ContactUs::first();
    return response()->json([
        'data' => $data,
    ]);
}


   
}
