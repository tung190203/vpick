<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xác minh email mới</title>
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
            background-color: #D72D36;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #D72D36;
            text-align: center;
            padding: 20px;
            background-color: #fff;
            border: 2px dashed #D72D36;
            border-radius: 5px;
            margin: 20px 0;
            letter-spacing: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Yêu cầu xác minh</h2>
    </div>
    <div class="content">
        <p>Xin chào,</p>
        <p>Đây là mã xác minh {{$type}} của bạn:</p>
        
        <div class="otp-code">{{ $otp }}</div>
        
        <p><strong>Lưu ý:</strong></p>
        <ul>
            <li>Mã OTP này sẽ hết hạn sau <strong>10 phút</strong></li>
            <li>Không chia sẻ mã này với bất kỳ ai</li>
            <li>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này và liên hệ với chúng tôi ngay</li>
        </ul>
        
        <p>Trân trọng,<br>Đội ngũ Picki</p>
    </div>
    <div class="footer">
        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
    </div>
</body>
</html>