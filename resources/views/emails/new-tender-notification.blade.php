<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tender Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f4f4f4;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .tender-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }
        .detail-row {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Tender Posted</h1>
    </div>

    <div class="content">
        <p>Dear {{ $user->name }},</p>

        <p>A new tender has been posted on the Tender Portal that may be of interest to you.</p>

        <div class="tender-details">
            <h2 style="color: #2563eb; margin-top: 0;">{{ $tender->title }}</h2>

            <div class="detail-row">
                <span class="detail-label">Tender Number:</span>
                <span class="detail-value">{{ $tender->tender_no }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Institution:</span>
                <span class="detail-value">{{ $tender->institution->institution_name ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Industry:</span>
                <span class="detail-value">{{ $tender->industry->name ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">County:</span>
                <span class="detail-value">{{ $tender->county->name ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Closing Date:</span>
                <span class="detail-value">{{ $tender->closing_date_and_time ? $tender->closing_date_and_time->format('Y-m-d H:i') : 'N/A' }}</span>
            </div>

            @if($tender->tender_fee_amount)
            <div class="detail-row">
                <span class="detail-label">Tender Fee:</span>
                <span class="detail-value">KSH {{ number_format($tender->tender_fee_amount, 2) }}</span>
            </div>
            @endif

            @if($tender->description)
            <div style="margin-top: 15px;">
                <span class="detail-label">Description:</span>
                <p style="margin-top: 5px;">{{ Str::limit($tender->description, 200) }}</p>
            </div>
            @endif

        </div>

        <center>
            <a href="{{ url('/login') }}" class="button">Login to View Full Details</a>
        </center>

        <p style="margin-top: 20px;">To view the complete tender details and download tender documents, please log in to your account on the Tender Portal.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}.</p>
        <p>If you no longer wish to receive these notifications, please update your preferences in your account settings.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>