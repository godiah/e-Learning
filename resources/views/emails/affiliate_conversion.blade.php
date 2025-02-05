<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Conversion on Your Affiliate Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background-color: #f7f7f7;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 20px;
        }

        .details {
            background-color: #fafafa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 15px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>New Conversion Alert!</h1>
    </div>
    <div class="content">
        <p>Hi {{ $affiliate->name }},</p>

        <p>Your affiliate link has just generated a new conversion🎇!</p>

        <div class="details">
            <h3>Conversion Details</h3>
            <p>
                <strong>Order ID:</strong> {{ $conversion->order_id }}<br>
                <strong>Course:</strong> {{ $conversion->affiliateLink->course->title ?? 'N/A' }}<br>
                <strong>Sale Amount:</strong> ${{ number_format($conversion->sale_amount, 2) }}<br>
                <strong>Commission Earned:</strong> ${{ number_format($commission->commission_amount, 2) }}<br>
            </p>
        </div>

        <p>The commission will be credited to your account. Funds will be made available to your account within 7 days.
        </p>

        <p>Keep sharing your link and earn more commissions!</p>

        <p>Best regards,<br>
            The Affiliate Team</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Coursio. All rights reserved.
    </div>
</body>

</html>
