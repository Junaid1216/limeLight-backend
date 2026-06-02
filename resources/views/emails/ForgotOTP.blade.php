<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP - {{ $headerTitle }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ asset('public/admin/assets/img/sahar_logo(1).png') }}" 
             alt="{{ config('app.name') }} Logo" 
             style="height: 100px; margin-bottom: 20px;">
    </div>

    <h2>Password Reset OTP</h2>

    <p>
        Use the OTP below to reset your password:
    </p>

    <div style="text-align: center; margin: 20px 0;">
        <span style="font-size: 24px; font-weight: bold; color: #021642;">{{ $otp }}</span>
    </div>

    <p>
        If you did not request this OTP, please ignore this email.
    </p>

    <p>
        Thanks,<br>
        <strong>{{ $headerTitle }}</strong>
    </p>
</body>
</html>
