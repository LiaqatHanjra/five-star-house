<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Project request</title>
</head>
<body style="margin:0;background:#131313;color:#e5e2e1;font-family:Arial,sans-serif;">
  <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
    <p style="letter-spacing:.18em;font-size:12px;color:#e50914;">FIVE STAR HOUSE</p>
    <h1 style="font-size:28px;line-height:1.2;margin:8px 0 16px;">
      {{ $audience === 'owner' ? 'A new project request' : 'We received your project' }}
    </h1>
    <p style="color:#e9bcb6;line-height:1.6;">
      {{ $audience === 'owner'
        ? 'Someone sent a project from the book page. The details are below.'
        : 'Thank you. The studio has your request and will get back to you as soon as possible.' }}
    </p>
    <table style="width:100%;border-collapse:collapse;margin-top:20px;">
      <tr><td style="padding:8px 0;color:#af8782;">Name</td><td>{{ $booking->full_name }}</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">Email</td><td>{{ $booking->email }}</td></tr>
      <tr><td style="padding:8px 0;color:#af8782;">Project</td><td>{{ $booking->project_type }}</td></tr>
    </table>
    @if($booking->message)
      <p style="margin-top:20px;line-height:1.6;white-space:pre-wrap;">{{ $booking->message }}</p>
    @endif
  </div>
</body>
</html>
