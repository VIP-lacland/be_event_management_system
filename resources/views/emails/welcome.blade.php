<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Eventify</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #ff2d95; text-align: center;">Welcome to Eventify!</h2>
        <p>Hello <strong>{{ $user->name }}</strong>,</p>
        <p>Thank you for registering an account with us. Your account has been created successfully.</p>
        <p>You can now log in using your email: <strong>{{ $user->email }}</strong></p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/login" style="background: linear-gradient(135deg, #ff2d95 0%, #ff7a00 100%); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Log In Now</a>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
            Best regards,<br>
            The Eventify Team
        </p>
    </div>
</body>
</html>
