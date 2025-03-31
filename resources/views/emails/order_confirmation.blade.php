<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .order-details {
            margin-bottom: 20px;
        }

        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .courses-table th,
        .courses-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .courses-table th {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase!</p>
        </div>

        <div class="order-details">
            <p>Order #: {{ $order->id }}</p>
            <p>Date: {{ $order->created_at->format('F j, Y') }}</p>
            <p>Total Amount: ${{ number_format($order->final_amount, 2) }}</p>
        </div>

        <h2>Courses Purchased</h2>
        <table class="courses-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->course->title }}</td>
                        <td>${{ number_format($item->final_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>

</html>
