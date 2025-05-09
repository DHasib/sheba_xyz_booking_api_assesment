<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Booking {{ $status }}</title>
</head>
<body>
  <p>Hi {{ $booking->user->name }},</p>

  <p>Your booking (ID: {{ $booking->unique_id }}) for
     <strong>{{ $booking->service->name }}</strong> on
     <strong>{{ $booking->scheduled_at }}</strong> has been
     <strong>{{ $status }}</strong>.
  </p>

  @if($booking->status === 'cancelled')
    <p>We're sorry for the inconvenience. Please contact us if you need to reschedule.</p>
  @endif

    @if($booking->status === 'completed')
        <p>We hope you enjoyed our service! If you have any feedback, please let us know.</p>
        <p>We would love to hear your thoughts on our service. Please take a moment to fill out our feedback form.</p>
        <a href="https://www.facebook.com">Feedback Form</a>
    @endif

  <p>Thank you for choosing our service!</p>
</body>
</html>
