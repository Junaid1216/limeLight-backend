<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_type' => 'required|in:staff,manager,asm,all',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'users' => 'required|array',
        ];
    }
}
