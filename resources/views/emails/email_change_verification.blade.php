<!-- resources/views/emails/email_change_verification.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <title>Verify Your New Email Address</title>
</head>

<body>
    <h1>Hello {{ $user->name }},</h1>
    <p>You've requested to change your email address. Please use the following code to verify your new email:</p>
    <h2>{{ $verificationCode }}</h2>
    <p>This code will expire in 15 minutes.</p>
</body>

</html>
