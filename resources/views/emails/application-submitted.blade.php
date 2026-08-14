<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application received</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #f5f7fb; margin: 0; padding: 24px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: #16a34a; color: #fff; padding: 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; line-height: 1.55; font-size: 14px; }
        .section-title { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 24px 0 8px; }
        .kv { display: table; width: 100%; margin: 4px 0; }
        .kv .k { display: table-cell; color: #6b7280; width: 40%; padding: 4px 8px 4px 0; vertical-align: top; }
        .kv .v { display: table-cell; color: #111827; padding: 4px 0; vertical-align: top; }
        ul { margin: 6px 0 0; padding-left: 20px; }
        li { margin: 3px 0; }
        .footer { padding: 16px 24px; background: #f9fafb; color: #6b7280; font-size: 12px; }
        .amend-note { margin-top: 16px; padding: 12px 14px; background: #ecfdf5; border-left: 3px solid #16a34a; color: #065f46; font-size: 13px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Application received</h1>
            <div style="font-size: 13px; margin-top: 6px; opacity: 0.9;">{{ $tender->title }}</div>
        </div>

        <div class="body">
            <p>Hi {{ $user->name }},</p>

            <p>
                We've received your application for
                <strong>{{ $tender->title }}</strong>
                @if ($tender->tender_no)
                    (<span>Tender No. {{ $tender->tender_no }}</span>)
                @endif.
                A summary is below for your records.
            </p>

            <div class="section-title">Submission</div>
            <div class="kv"><span class="k">Submitted at</span><span class="v">{{ $submittedAt?->format('D, d M Y H:i') ?? '—' }}</span></div>
            <div class="kv"><span class="k">Reference{{ count($applications) > 1 ? 's' : '' }}</span>
                <span class="v">
                    @foreach ($applications as $app)
                        #{{ $app->id }}@if (! $loop->last), @endif
                    @endforeach
                </span>
            </div>

            @if ($primary)
                <div class="section-title">Applicant</div>
                <div class="kv"><span class="k">Company / Organization</span><span class="v">{{ $primary->company_name }}</span></div>
                @if ($primary->email)
                    <div class="kv"><span class="k">Email</span><span class="v">{{ $primary->email }}</span></div>
                @endif
                @if ($primary->telephone)
                    <div class="kv"><span class="k">Telephone</span><span class="v">{{ $primary->telephone }}</span></div>
                @endif
                @if ($primary->address)
                    <div class="kv"><span class="k">Address</span><span class="v">{{ $primary->address }}</span></div>
                @endif

                <div class="section-title">Contact person</div>
                <div class="kv"><span class="k">Name</span><span class="v">{{ $primary->representative_name ?? '—' }}</span></div>
                @if ($primary->representative_position)
                    <div class="kv"><span class="k">Position</span><span class="v">{{ $primary->representative_position }}</span></div>
                @endif
                @if ($primary->representative_email)
                    <div class="kv"><span class="k">Email</span><span class="v">{{ $primary->representative_email }}</span></div>
                @endif
                @if ($primary->representative_telephone)
                    <div class="kv"><span class="k">Telephone</span><span class="v">{{ $primary->representative_telephone }}</span></div>
                @endif
            @endif

            @if (count($categories))
                <div class="section-title">Categories applied for ({{ count($categories) }})</div>
                <ul>
                    @foreach ($categories as $c)
                        <li><strong>{{ $c->tender_no }}</strong> &mdash; {{ $c->title }}</li>
                    @endforeach
                </ul>
            @endif

            @if (count($requirementFiles))
                <div class="section-title">Uploaded documents</div>
                <ul>
                    @foreach ($requirementFiles as $f)
                        <li>{{ $f->file_name }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($primary && $primary->filled_questionnaire_file_name)
                <div class="section-title">Confidential Business Questionnaire</div>
                <div>{{ $primary->filled_questionnaire_file_name }}</div>
            @endif

            @if ($canAmendUntil && $canAmendUntil->isFuture())
                <div class="amend-note">
                    You can still make changes to this submission until
                    <strong>{{ $canAmendUntil->format('D, d M Y H:i') }}</strong>
                    &mdash; visit <em>My Applications</em> and click "Unsubmit &amp; Amend".
                </div>
            @endif
        </div>

        <div class="footer">
            Thank you for using our tender platform.
        </div>
    </div>
</body>
</html>
