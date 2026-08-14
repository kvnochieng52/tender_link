<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reminder: complete your application</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #f5f7fb; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: #d97706; color: #fff; padding: 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; line-height: 1.6; font-size: 14px; }
        .cta { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #16a34a; color: #fff !important; text-decoration: none; border-radius: 4px; font-weight: 600; }
        .meta { margin-top: 16px; padding: 12px 14px; background: #fef3c7; border-left: 3px solid #d97706; color: #78350f; font-size: 13px; border-radius: 4px; }
        .footer { padding: 16px 24px; background: #f9fafb; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>You have a pending tender application</h1>
        </div>

        <div class="body">
            <p>Hi {{ $user->name }},</p>

            <p>
                You started an application for
                <strong>{{ $tender->title }}</strong>
                @if ($tender->tender_no)
                    (<span>Tender No. {{ $tender->tender_no }}</span>)
                @endif
                but haven't submitted it yet. Your progress is saved &mdash; you can pick up right
                where you left off.
            </p>

            @if ($closingDate)
                <div class="meta">
                    <strong>Deadline:</strong>
                    {{ $closingDate->format('D, d M Y H:i') }}
                    &mdash; that's
                    {{ $closingDate->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}
                    from now.
                </div>
            @endif

            <p>
                <a href="{{ $resumeUrl }}" class="cta">Resume application</a>
            </p>

            <p style="color:#6b7280; font-size: 12px;">
                Last saved: {{ $draft->updated_at->format('D, d M Y H:i') }}
            </p>
        </div>

        <div class="footer">
            We'll send at most one reminder every few days until you submit or the deadline passes.
        </div>
    </div>
</body>
</html>
