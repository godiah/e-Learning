<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Email Address</title>
</head>

<body>
    <h1>Hello {{ $user->name }},</h1>
    <p>Thank you for registering. Please use the following code to verify your email address:</p>
    <h2>{{ $verificationCode }}</h2>
    <p>This code will expire in 5 minutes.</p>
</body>

</html>
