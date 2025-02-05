<!DOCTYPE html>
<html>

<head>
    <title>Your Admin Account Details</title>
</head>

<body>
    <h1>Welcome, {{ $details['name'] }}!</h1>
    <p>Your admin account has been created. Below are your details:</p>
    <ul>
        <li><strong>Email:</strong> {{ $details['email'] }}</li>
        <li><strong>Role:</strong> {{ $details['role'] }}</li>
        <li><strong>Password:</strong> {{ $details['password'] }}</li>
    </ul>
    <p>Please log in and change your password immediately.</p>
</body>

</html>
