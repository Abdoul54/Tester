<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}!</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Welcome to {{ config('app.name') }}! We're excited to have you join our community.</p>
        
        <p>Your account has been successfully created with the email address: <strong>{{ $user->email }}</strong></p>
        
        <p>Here are a few things you can do to get started:</p>
        <ul>
            <li>Complete your profile</li>
            <li>Explore our features</li>
            <li>Connect with other users</li>
        </ul>
        
        <a href="{{ config('app.url') }}" class="btn">Get Started</a>
        
        <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
        
        <p>Best regards,<br>The {{ config('app.name') }} Team</p>
    </div>
    
    <div class="footer">
        <p>This email was sent to {{ $user->email }}. If you didn't create an account, please contact us immediately.</p>
    </div>
</body>
</html>