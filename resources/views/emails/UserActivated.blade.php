<!DOCTYPE html>
<html>
<head>
    <title>Customer Activation Email</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ asset('public/admin/assets/img/sahar_logo(1).png') }}" alt="{{ config('app.name') }} Logo" style="height: 100px;">
    </div>

    <h2>Hi {{ $message['name'] }},</h2>

    <p>
        Congratulations! Your account has been <strong>Activated</strong> by the <strong>Common</strong> team.
    </p>

    <p>
        Thanks,<br>
        <strong>Common</strong>
    </p>
</body>
</html>
