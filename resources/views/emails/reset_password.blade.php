<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            padding: 30px;
            margin: 0;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .email-header {
            color: #BA110B;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .email-body {
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
        }
        .reset-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #BA110B;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .email-footer {
            margin-top: 30px;
            font-size: 14px;
            color: #888888;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">Yêu cầu đặt lại mật khẩu</div>
        <div class="email-body">
            <p>Xin chào,</p>
            <p>Bạn vừa gửi yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
            <p>Vui lòng nhấn vào nút bên dưới để tiếp tục:</p>
            <p>
                <a href="{{ $resetLink }}" class="reset-button">Đặt lại mật khẩu</a>
            </p>
            <p>Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email này một cách an toàn.</p>
        </div>
        <div class="email-footer">
            Trân trọng,<br>
            Đội ngũ hỗ trợ
        </div>
    </div>
</body>
</html>
