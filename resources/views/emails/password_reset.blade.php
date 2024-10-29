<!DOCTYPE html>
<html>

<head>
    <title>Reset Your Password</title>
</head>

<body>
    <h1>Hello {{ $user->name }},</h1>
    <p>You've requested to reset your password. Please use the following code to reset your password:</p>
    <h2>{{ $resetCode }}</h2>
    <p>This code will expire in 2 minutes.</p>
</body>

</html>
