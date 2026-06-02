@component('mail::message')
Dear {{ $message['name'] }},

Your account as a Sub-Admin has been successfully created. Below are your login credentials:

**Email:** {{ $message['email'] }}

**Password:** {{ $message['password'] }}


Thanks,<br>
{{ config('app.name') }}
@endcomponent