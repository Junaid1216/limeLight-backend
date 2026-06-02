<!DOCTYPE html>
<html>

<head>
    <title>Account Deactivation Notification</title>
</head>

<body>
    <h2>Account Deactivation Notice</h2>
    <p>Dear {{ $name }},</p>

    <p>Your account has been deactivated by the administrator.</p>

    @if (!empty($reason))
        <p><strong>Reason:</strong> {{ $reason }}</p>
    @endif

    <p>If you believe this is a mistake or have any questions, please contact our support team.</p>

    <p>Best regards,<br>
        {{ config('app.name') }}</p>
</body>

</html>
