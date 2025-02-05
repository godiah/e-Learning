<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Affiliate Approval Notification</title>
</head>

<body>
    <h3>Welcome, {{ $user->name }}!</h3>
    <div class="content">
        <p>Congratulations! Your affiliate application has been approved.</p>

        <h3>Terms and Conditions:</h3>
        <ul>
            <li>All affiliates must comply with our advertising policies.</li>
            <li>Commissions will be paid out monthly, provided the minimum payout threshold is met.</li>
            <li>Affiliates may not engage in fraudulent or deceptive practices.</li>
            <li>The affiliate link code must not be shared on coupon or deal websites.</li>
            <li>We reserve the right to revoke your affiliate status if any terms are violated.</li>
        </ul>

        <p>If you have any questions or need assistance, feel free to contact us at support@example.com.</p>
    </div>
    <div class="footer">
        <p>Thank you for being a part of our affiliate program!</p>
    </div>
</body>

</html>
