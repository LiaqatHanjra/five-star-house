<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Booking confirmed</title>
</head>
<body style="margin:0;background:#131313;color:#e5e2e1;font-family:Arial,sans-serif;">
  <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
    <p style="letter-spacing:.18em;font-size:12px;color:#e50914;">FIVE STAR HOUSE</p>
    <h1 style="font-size:28px;line-height:1.2;margin:8px 0 16px;">
      {{ $audience === 'owner' ? 'A booking is paid and confirmed' : 'Your booking is confirmed' }}
    </h1>
    <p style="color:#e9bcb6;line-height:1.6;">
      {{ $audience === 'owner'
        ? 'A customer has paid for studio time. The details are below.'
        : 'Payment was received. The studio is holding this time for you.' }}
    </p>
    <table style="width:100%;border-collapse:collapse;margin-top:20px;">
      <tr><td style="padding:8px 0;color:#af8782;">Reference</td><td>{{ $booking->reference }}</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">Booking</td><td>{{ $booking->project_type }}</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">Customer</td><td>{{ $booking->full_name }} · {{ $booking->email }}</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">When</td><td>{{ optional($booking->booking_date)->toDateString() }} · {{ substr((string) $booking->start_time, 0, 5) }} for {{ $booking->hours }} hours</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">Total</td><td>${{ number_format((float) $booking->amount, 2) }} CAD</td></tr>
    </table>
    @if($booking->message)
      <p style="margin-top:20px;line-height:1.6;">{{ $booking->message }}</p>
    @endif
  </div>
</body>
</html>
