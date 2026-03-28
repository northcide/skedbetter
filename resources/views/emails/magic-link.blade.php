<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; padding: 40px 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { font-size: 20px; color: #111827; margin: 0 0 8px; }
        p { font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0 0 16px; }
        .btn { display: inline-block; background: #1a75f5; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .expire { font-size: 12px; color: #9ca3af; margin-top: 20px; }
        .footer { text-align: center; font-size: 11px; color: #d1d5db; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign in to SkedBetter</h1>
        <p>Click the button below to log in. This link expires in {{ $magicLink->expires_at->diffForHumans(null, true) }}.</p>
        <a href="{{ $magicLink->getUrl() }}" class="btn">Sign In</a>
        <p class="expire">If you didn't request this, you can safely ignore this email.</p>
        <p style="font-size: 11px; color: #d1d5db; word-break: break-all;">{{ $magicLink->getUrl() }}</p>
    </div>
    <div class="footer">SkedBetter &mdash; Field scheduling made simple</div>
</body>
</html>
