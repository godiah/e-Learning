<!DOCTYPE html>
<html>

<head>
    <title>Account Locked</title>
</head>

<body>
    <p>Dear {{ $user->name }},</p>
    <p>
        Your account has been locked due to multiple failed login attempts. If this was not you, please contact us
        immediately to secure your account.
        If you made these attempts, please wait for 1 hour before trying again.
    </p>
    <p>
        If you have any concerns, feel free to contact our support team.
    </p>
    <p>Best regards,</p>
</body>

</html>
