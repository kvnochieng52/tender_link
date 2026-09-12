<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acknowledgement of receipt</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #f5f7fb; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .body { padding: 32px 28px; line-height: 1.6; font-size: 14px; color: #1f2937; }
        .body p { margin: 0 0 14px; }
        .refs { margin: 16px 0; padding: 12px 14px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 4px; font-size: 13px; color: #065f46; }
        .refs code { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace; background: #dcfce7; padding: 1px 5px; border-radius: 3px; font-size: 12px; color: #065f46; }
        .signature { margin-top: 24px; font-weight: 600; }
        .footer { padding: 14px 24px; background: #f9fafb; color: #9ca3af; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
    @php
        $bidderName = $primary?->company_name ?: $user->name;
        $bidTitle = $tender->title;
    @endphp

    <div class="card">
        <div class="body">
            <p>Dear {{ $bidderName }},</p>

            <p>We thank you for your participation in the procurement process for {{ $bidTitle }}.</p>

            <p>We confirm receipt of your application. Your submission is currently undergoing evaluation and due diligence in accordance with the applicable procurement requirements.</p>

            @if (count($applications))
                <div class="refs">
                    <strong>Your application reference{{ count($applications) > 1 ? 's' : '' }}:</strong>
                    @foreach ($applications as $a)
                        <div style="margin-top: 4px;">
                            <code>{{ $a->application_no ?: ('#' . $a->id) }}</code>
                            @if ($a->tenderCategory)
                                <span style="margin-left: 6px; color:#6b7280;">
                                    {{ $a->tenderCategory->tender_no }} — {{ $a->tenderCategory->title }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                    <div style="margin-top: 8px; color:#6b7280; font-size: 12px;">
                        Please quote {{ count($applications) > 1 ? 'these references' : 'this reference' }} in any future correspondence.
                    </div>
                </div>
            @endif

            <p>Please note that the submission of an application does not constitute an award or commitment to engage your organization.</p>

            <p>The results of the evaluation will be communicated to participating bidders in due course.</p>

            <p>This is an auto-generated email. Please do not reply to this email. For inquiries or assistance, please contact <a href="mailto:info@tenderplug.com">info@tenderplug.com</a>.</p>

            <p class="signature">TenderPlug Procurement Team</p>
        </div>
        <div class="footer">
            &copy; TenderPlug
        </div>
    </div>
</body>
</html>
