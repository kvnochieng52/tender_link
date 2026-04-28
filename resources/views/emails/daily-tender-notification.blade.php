<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tender Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header .subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 10px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .summary-box {
            background-color: #e0f2fe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .tender-list {
            margin: 20px 0;
        }
        .tender-item {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .tender-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .tender-title {
            color: #2563eb;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .tender-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .detail-item {
            display: flex;
            align-items: flex-start;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            margin-right: 8px;
            min-width: 100px;
        }
        .detail-value {
            color: #374151;
            flex: 1;
        }
        .tender-description {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #4b5563;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #fef3c7;
            color: #92400e;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .urgent-badge {
            background-color: #fee2e2;
            color: #991b1b;
        }
        @media (max-width: 600px) {
            .tender-details {
                grid-template-columns: 1fr;
            }
            .detail-item {
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ $tenders->count() }} New Tender{{ $tenders->count() > 1 ? 's' : '' }} Available</h1>
            <div class="subtitle">
                @if($timeOfDay === 'morning')
                    Good morning! Here are the latest tender opportunities
                @else
                    Good evening! Check out today's new tender opportunities
                @endif
            </div>
        </div>

        <div class="content">
            <div class="greeting">
                Dear {{ $user->name }},
            </div>

            <div class="summary-box">
                <strong>{{ $tenders->count() }} new tender{{ $tenders->count() > 1 ? 's have' : ' has' }} been posted</strong> since our last notification.
                @if($timeOfDay === 'morning')
                    Start your day by exploring these fresh opportunities!
                @else
                    Review these opportunities before the end of the business day.
                @endif
            </div>

            <div class="tender-list">
                @foreach($tenders as $tender)
                <div class="tender-item">
                    <h3 class="tender-title">
                        {{ $tender->title }}
                        @php
                            $daysUntilClosing = now()->diffInDays($tender->closing_date_and_time);
                        @endphp
                        @if($daysUntilClosing <= 3)
                            <span class="badge urgent-badge">Closing Soon</span>
                        @elseif($tender->created_at->isToday())
                            <span class="badge">New Today</span>
                        @endif
                    </h3>

                    <div class="tender-details">
                        <div class="detail-item">
                            <span class="detail-label">Tender No:</span>
                            <span class="detail-value">{{ $tender->tender_no }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Institution:</span>
                            <span class="detail-value">{{ $tender->institution->institution_name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Industry:</span>
                            <span class="detail-value">{{ $tender->industry->name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">County:</span>
                            <span class="detail-value">{{ $tender->county->name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Closing Date:</span>
                            <span class="detail-value">{{ $tender->closing_date_and_time ? $tender->closing_date_and_time->format('M j, Y g:i A') : 'N/A' }}</span>
                        </div>
                        @if($tender->tender_fee_amount)
                        <div class="detail-item">
                            <span class="detail-label">Tender Fee:</span>
                            <span class="detail-value">KSH {{ number_format($tender->tender_fee_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>

                    @if($tender->description)
                    <div class="tender-description">
                        <strong>Description:</strong><br>
                        {{ Str::limit($tender->description, 250) }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            <center>
                <a href="{{ url('/login') }}" class="button">View All Tenders & Download Documents</a>
            </center>

            <p style="margin-top: 20px; color: #6b7280;">
                Log in to your account to:
                <ul style="color: #6b7280;">
                    <li>View complete tender details and requirements</li>
                    <li>Download tender documents</li>
                    <li>Track tender deadlines</li>
                    <li>Submit your applications</li>
                </ul>
            </p>
        </div>

        <div class="footer">
            <p>You're receiving this notification because you're subscribed to tender alerts on {{ config('app.name') }}.</p>
            <p>
                @if($timeOfDay === 'morning')
                    Next notification will be sent this evening at 4:00 PM.
                @else
                    Next notification will be sent tomorrow morning at 8:00 AM.
                @endif
            </p>
            <p>
                <a href="{{ url('/account/preferences') }}">Update notification preferences</a> |
                <a href="{{ url('/unsubscribe') }}">Unsubscribe</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>