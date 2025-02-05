<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>New Affiliate Link Notification</title>
</head>

<body>
    <h3>Hello, {{ $user }} 🎉</h3>
    <div class="content">
        <p>Congratulations! Your affiliate application has been approved.</p>

        You have successfully generated a new **Affiliate Link** for the course:

        <li>## **{{ $courseName }}**</li>

        ### Your Tracking Details:
        <li>- **Tracking Code:** `{{ $trackingCode }}`</li>
        <li>- **Affiliate Link:** [Click here]({{ $shortUrl }})</li>


        Start sharing your link and earn commissions! 🚀
    </div>
    <div class="footer">
        <p>If you have any questions or need assistance, feel free to contact us at support@example.com.</p>

        Thanks,
        {{ config('app.name') }}
    </div>
</body>

</html>
