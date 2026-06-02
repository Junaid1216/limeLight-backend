<!DOCTYPE html>
<html>
<head>
    <title>New Contact Us Message - Common</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ asset('public/admin/assets/img/sahar_logo(1).png') }}" 
             alt="{{ config('app.name') }} Logo" 
             style="height: 100px;">
    </div>

    <h2>New Contact Us Message</h2>

    <p>You have received a new message from your website's contact form.</p>

    <p><strong>Email:</strong> {{ $email }}</p>

    <p><strong>Message:</strong><br>
    {{ $message }}</p>

    <p style="margin: 30px 0; text-align: center;">
        <a href="mailto:{{ $email }}" 
           style="display: inline-block; padding: 10px 20px; background-color: #021642; color: #fff; text-decoration: none; border-radius: 5px;">
            Reply to {{ $email }}
        </a>
    </p>

    <p>Thanks,<br>
    <strong>Common</strong></p>
</body>
</html>
