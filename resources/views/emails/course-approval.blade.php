<!DOCTYPE html>
<html>

<head>
    <title>Course Approval Status</title>
</head>

<body>
    <h1>Your Course "{{ $courseTitle }}" has been {{ $status }}.</h1>

    @if ($status === 'approved')
        <p>Congratulations! Your course is now live on the platform.</p>
    @else
        <p>We regret to inform you that your course application was rejected. Please review your submission and consider
            resubmitting.</p>
    @endif

    <p>Thank you for using our platform!</p>
</body>

</html>
