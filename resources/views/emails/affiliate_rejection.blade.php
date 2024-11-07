<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Affiliate Application Rejection Notification</title>
</head>

<body>
    <div class="header">
        <h1>Affiliate Application Rejection</h1>
    </div>
    <div class="content">
        <p>Dear {{ $user->name }},</p>
        <p>We regret to inform you that your affiliate application has been rejected. Below are some possible reasons
            for this decision:</p>
        <ul>
            <li>Your account is not in good standing.</li>
            <li>Inadequate promotional strategies or methods provided.</li>
            <li>Failure to meet our affiliate program eligibility criteria.</li>
            <li>Past violations of our terms and conditions.</li>
            <li>Incomplete or inaccurate application information.</li>
        </ul>
        <p>Please note that you can reapply for the affiliate program after addressing the above concerns. We encourage
            you to take the necessary steps and submit a new application in the future.</p>
        <p>If you have any questions or would like further clarification regarding your application, feel free to
            contact us at support@example.com.</p>
    </div>
    <div class="footer">
        <p>Thank you for your interest in our affiliate program.</p>
    </div>
</body>

</html>
