<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #f5f7fb; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: #0f766e; color: #fff; padding: 20px 24px; }
        .header .subject { font-size: 18px; font-weight: 600; margin: 0; }
        .header .meta { font-size: 12px; margin-top: 6px; opacity: 0.9; }
        .body { padding: 28px 24px; line-height: 1.6; font-size: 14px; color: #1f2937; white-space: pre-wrap; }
        .ref { margin: 20px 0 0; padding: 12px 14px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 4px; font-size: 12px; color: #065f46; }
        .ref code { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace; background: #dcfce7; padding: 1px 5px; border-radius: 3px; }
        .footer { padding: 14px 24px; background: #f9fafb; color: #9ca3af; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <p class="subject">{{ $subject }}</p>
            <div class="meta">
                {{ $tender->title }}
                @if ($tender->tender_no)
                    &middot; Tender No. {{ $tender->tender_no }}
                @endif
            </div>
        </div>

        <div class="body">@if ($recipientName)Dear {{ $recipientName }},

@endif{{ $body }}</div>

        @if ($application)
            <div style="padding: 0 24px;">
                <div class="ref">
                    <strong>Your application reference:</strong>
                    <code>{{ $application->application_no ?: ('#' . $application->id) }}</code>
                    <div style="margin-top: 6px; color:#6b7280;">
                        Please quote this reference in any reply.
                    </div>
                </div>
            </div>
        @endif

        <div class="footer">
            This message was sent by the TenderPlug procurement team. Do not reply to this email.
            For enquiries contact <a href="mailto:info@tenderplug.com">info@tenderplug.com</a>.
        </div>
    </div>
</body>
</html>
