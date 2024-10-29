<!DOCTYPE html>
<html>

<head>
    <title>Course Submitted for Approval</title>
</head>

<body>
    <h1>Dear Instructor,</h1>

    <p>Your course "{{ $courseApproval->title }}" has been successfully submitted for approval.</p>

    <p>Here are the details of your submission:</p>
    <ul>
        <li>Title: {{ $courseApproval->title }}</li>
        <li>Description: {{ Str::limit($courseApproval->description, 100) }}</li>
        <li>Price: ${{ number_format($courseApproval->price, 2) }}</li>
        <li>Level: {{ ucfirst($courseApproval->level) }}</li>
    </ul>

    <p>Our team will review your course and get back to you soon. You will be notified via email once a decision has
        been made.

        Thank you for your contribution to our learning platform!!</p>

    <p>Best regards,</p>
    <p>The Team</p>
</body>

</html>
