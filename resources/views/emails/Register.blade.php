<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Common</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="text-align:center; margin-bottom: 20px;">
        <img src="{{ asset('public/admin/assets/img/sahar_logo(1).png') }}" 
             alt="{{ config('app.name') }} Logo" 
             style="height: 100px; margin-bottom: 20px;">
        <h3><strong>Welcome to <span style="color: #021642;">Common</span></strong></h3>
    </div>

    <p>Dear {{ $name ?? 'User' }},</p>

    <p>Your account has been successfully created.</p>

    <p>With your account, you’ll be able to:</p>
    <ul>
        <li>Book cars</li>
        <li>Earn loyalty points</li>
    </ul>

    <h3>Your Account Details:</h3>
    <ul>
        <li><strong>Email:</strong> {{ $email ?? 'N/A' }}</li>
        <li><strong>Phone:</strong> {{ $phone ?? 'N/A' }}</li>
    </ul>

    <p>Please keep this information safe and secure. Do not share your login credentials with anyone.</p>

    <p>If you have any questions or need assistance, feel free to contact our support team anytime.</p>

    <p>Thanks,<br><strong>Common</strong></p>
</body>
</html>
