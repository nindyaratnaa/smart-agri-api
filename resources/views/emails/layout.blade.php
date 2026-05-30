<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .card { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #16a34a; color: white; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 24px; color: #374151; line-height: 1.6; }
        .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 16px; margin: 16px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #d1fae5; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #6b7280; font-size: 13px; }
        .value { font-weight: 600; color: #111827; }
        .btn { display: inline-block; background: #16a34a; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin-top: 16px; }
        .footer { padding: 16px 24px; background: #f9fafb; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h1>🌾 Smart Agriculture</h1>
        <p style="margin: 4px 0 0; opacity: 0.85; font-size: 14px;">Platform Integrasi Data Pertanian Nasional</p>
    </div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Smart Agriculture — Universitas Brawijaya · GEMASTIK XVII
    </div>
</div>
</body>
</html>
