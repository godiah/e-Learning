<!DOCTYPE html>
<html>

<head>
    <title>Instructor Application Rejected</title>
</head>

<body>
    <h1>Dear {{ $user->name }},</h1>
    <p>We regret to inform you that your instructor application has been rejected.</p>
    <p><strong>Reason for rejection:</strong> {{ $reason }}</p>
    <p>We encourage you to review the reasons for rejection and resubmit your application if you feel you've made the
        necessary changes.</p>
    <p>If you have any questions or need further clarification, feel free to reach out to us.</p>
    <p>Best regards,<br>The Team</p>
</body>

</html>
