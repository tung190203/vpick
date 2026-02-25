<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo CLB mới</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #D72D36;">Báo cáo CLB mới</h2>
    <p>Có báo cáo mới từ người dùng về CLB. Chi tiết như sau:</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">CLB bị báo cáo</th>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">Người báo cáo</th>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">Email người báo cáo</th>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">Phân loại lý do</th>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">Nội dung báo cáo</th>
                <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; text-align: left; background: #f5f5f5;">Thời gian</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->club?->name ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->reporter?->full_name ?? $report->reporter?->email ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->reporter?->email ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->reason_type?->label() ?? 'N/A' }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->description ?? 'Không có' }}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $report->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 20px; color: #666; font-size: 14px;">Vui lòng xem xét và xử lý báo cáo này.</p>
</body>
</html>
