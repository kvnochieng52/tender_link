<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Evaluation Report — {{ $tender->tender_no ?? $tender->title }}</title>
    <style>
        @media print { .no-print { display: none !important; } body { background: #fff; } }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #111827; background: #f5f7fb; margin: 0; padding: 24px; }
        .paper { max-width: 900px; margin: 0 auto; background: #fff; padding: 32px 40px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        h1 { margin: 0 0 6px; font-size: 22px; }
        h2 { font-size: 15px; margin: 24px 0 8px; border-bottom: 2px solid #16a34a; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f9fafb; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.03em; color: #6b7280; }
        .status-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .p-recommended { background: #d1fae5; color: #065f46; }
        .p-eligible    { background: #dbeafe; color: #1e40af; }
        .p-disqualified{ background: #fee2e2; color: #991b1b; }
        .p-pending     { background: #fef3c7; color: #78350f; }
        .kbd { font-family: 'SFMono-Regular', Consolas, monospace; background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-size: 11px; }
        .no-print { text-align: right; margin-bottom: 12px; }
        .no-print button { background: #16a34a; color: #fff; border: 0; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .footer { margin-top: 30px; color: #9ca3af; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">📄 Save as PDF / Print</button>
    </div>

    <div class="paper">
        <h1>Evaluation Report</h1>
        <div class="meta">
            {{ $tender->tender_no ?? '—' }} · {{ $tender->title }}<br>
            {{ $tender->institution?->institution_name ?? '—' }}
            &middot; Closes {{ optional($tender->closing_date_and_time)->format('D, d M Y H:i') ?? '—' }}
            &middot; Generated {{ now()->format('D, d M Y H:i') }}
        </div>

        <h2>Evaluation weights</h2>
        <table>
            <thead>
                <tr>
                    <th>Section</th><th style="text-align:right">Weight</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Compliance</td><td style="text-align:right">{{ number_format((float) $weights['compliance'], 2) }}%</td></tr>
                <tr><td>Technical</td><td style="text-align:right">{{ number_format((float) $weights['technical'], 2) }}%</td></tr>
                <tr><td>Financial</td><td style="text-align:right">{{ number_format((float) $weights['financial'], 2) }}%</td></tr>
            </tbody>
        </table>

        <h2>Ranking</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Application</th>
                    <th>Bidder</th>
                    <th style="text-align:center">Compliance</th>
                    <th style="text-align:right">Technical</th>
                    <th style="text-align:right">Financial</th>
                    <th style="text-align:right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    @php
                        $statusText = $r['overall_status'] ?? 'Pending';
                        $statusClass = match ($statusText) {
                            'Recommended'  => 'p-recommended',
                            'Eligible'     => 'p-eligible',
                            'Disqualified' => 'p-disqualified',
                            default        => 'p-pending',
                        };
                    @endphp
                    <tr>
                        <td>{{ $r['rank'] ?? '—' }}</td>
                        <td><span class="kbd">{{ $r['application_no'] ?? '' }}</span></td>
                        <td>
                            {{ $r['company_name'] }}
                            @if (! empty($r['category']))
                                <div style="font-size:10px;color:#6b7280">{{ $r['category'] }}</div>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <span class="status-pill {{ ($r['compliance_status'] ?? '') === 'Pass' ? 'p-recommended' : (($r['compliance_status'] ?? '') === 'Fail' ? 'p-disqualified' : 'p-pending') }}">
                                {{ $r['compliance_status'] ?? '—' }}
                            </span>
                        </td>
                        <td style="text-align:right">{{ $r['technical_score'] !== null ? number_format((float) $r['technical_score'], 2) : '—' }}</td>
                        <td style="text-align:right">{{ $r['financial_score'] !== null ? number_format((float) $r['financial_score'], 2) : '—' }}</td>
                        <td style="text-align:right"><strong>{{ $r['total_score'] !== null ? number_format((float) $r['total_score'], 2) : '—' }}</strong></td>
                        <td>
                            <span class="status-pill {{ $statusClass }}">{{ $statusText }}</span>
                            @if (! empty($r['disqualification_reason']))
                                <div style="font-size:10px;color:#991b1b;margin-top:2px">
                                    {{ $r['disqualification_reason'] }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($tender->award)
            <h2>Award</h2>
            <p style="font-size:13px">
                Awarded to <strong>{{ $tender->award->application?->company_name ?? '—' }}</strong>
                (<span class="kbd">{{ $tender->award->application?->application_no ?? '—' }}</span>)
                @if ($tender->award->contract_value)
                    &middot; Contract value <strong>KES {{ number_format((float) $tender->award->contract_value) }}</strong>
                @endif
                @if ($tender->award->reference_no)
                    &middot; Ref {{ $tender->award->reference_no }}
                @endif
                &middot; Awarded {{ optional($tender->award->awarded_at)->format('d M Y') }}
            </p>
        @endif

        <div class="footer">
            TenderPlug — Report generated {{ now()->format('D, d M Y H:i') }} · Recommended based on the configured evaluation criteria. Final award subject to the authorized procurement decision-maker.
        </div>
    </div>
</body>
</html>
