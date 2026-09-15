<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? '通知' }}</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#0f2744;font-family:'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:24px 12px">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fff;border:1px solid #dbe3ee;border-radius:12px;padding:28px 28px 24px">
                <tr>
                    <td>
                        <p style="margin:0 0 8px;font-size:13px;color:#64748b">{{ $siteName ?? config('app.name') }}</p>
                        <h1 style="margin:0 0 16px;font-size:20px;line-height:1.4">{{ $heading }}</h1>
                        <div style="font-size:15px;line-height:1.7;color:#334155">
                            @yield('content')
                        </div>
                        @if(!empty($actionUrl) && !empty($actionText))
                            <p style="margin:24px 0 0">
                                <a href="{{ $actionUrl }}" style="display:inline-block;padding:10px 16px;background:#1b4f72;color:#fff;text-decoration:none;border-radius:8px">{{ $actionText }}</a>
                            </p>
                            <p style="margin:12px 0 0;font-size:12px;color:#64748b;word-break:break-all">{{ $actionUrl }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
