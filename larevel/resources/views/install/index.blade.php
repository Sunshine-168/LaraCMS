<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CMS 安装向导</title>
    <style>
        body{margin:0;font-family:"Noto Sans SC","Segoe UI",sans-serif;background:#f4f7fb;color:#0f2744}
        .box{max-width:640px;margin:40px auto;background:#fff;border:1px solid #dbe3ee;border-radius:12px;padding:28px}
        h1{margin:0 0 8px;font-size:22px} .muted{color:#64748b;font-size:14px}
        table{width:100%;border-collapse:collapse;margin:16px 0}
        td{padding:8px 0;border-bottom:1px solid #eef2f7}
        .ok{color:#157347}.bad{color:#b02a37}
        .btn{display:inline-block;padding:10px 16px;background:#1b4f72;color:#fff;border:0;border-radius:6px;cursor:pointer}
        .flash{background:#f8d7da;color:#842029;padding:10px;border-radius:6px;margin-bottom:12px}
        label{display:block;margin:14px 0 6px;font-size:14px}
        input[type=text],input[type=email],input[type=password]{width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #dbe3ee;border-radius:6px}
        .check{display:flex;gap:8px;align-items:flex-start;margin:16px 0}
        .check input{margin-top:3px}
    </style>
</head>
<body>
<div class="box">
    <h1>CMS 安装向导</h1>
    <p class="muted">请先配置好 <code>.env</code> 数据库连接，再执行安装。不勾选演示数据也会创建超级管理员。</p>
    @if(session('error'))<div class="flash">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="flash">{{ $errors->first() }}</div>@endif

    <table>
        <tr><td>PHP ≥ 8.2</td><td class="{{ $checks['php']?'ok':'bad' }}">{{ $checks['php']?'通过':'不通过' }} ({{ PHP_VERSION }})</td></tr>
        <tr><td>PDO</td><td class="{{ $checks['pdo']?'ok':'bad' }}">{{ $checks['pdo']?'通过':'缺失' }}</td></tr>
        <tr><td>mbstring</td><td class="{{ $checks['mbstring']?'ok':'bad' }}">{{ $checks['mbstring']?'通过':'缺失' }}</td></tr>
        <tr><td>openssl</td><td class="{{ $checks['openssl']?'ok':'bad' }}">{{ $checks['openssl']?'通过':'缺失' }}</td></tr>
        <tr><td>storage 可写</td><td class="{{ $checks['storage']?'ok':'bad' }}">{{ $checks['storage']?'通过':'不可写' }}</td></tr>
        <tr><td>.env 存在</td><td class="{{ $checks['env']?'ok':'bad' }}">{{ $checks['env']?'通过':'缺失' }}</td></tr>
        <tr><td>数据库连接</td><td class="{{ $dbOk?'ok':'bad' }}">{{ $dbOk?'通过':('失败：'.$dbError) }}</td></tr>
    </table>

    @php $ready = !in_array(false, $checks, true) && $dbOk; @endphp
    @if($ready)
        <form method="post" action="{{ url('/install') }}">
            @csrf
            <label>站点名称</label>
            <input type="text" name="site_name" value="{{ old('site_name', '综合 CMS') }}" required>
            <label>管理员姓名</label>
            <input type="text" name="admin_name" value="{{ old('admin_name', '超级管理员') }}" required>
            <label>管理员邮箱</label>
            <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@example.com') }}" required>
            <label>管理员密码（至少 8 位）</label>
            <input type="password" name="admin_password" required minlength="8">
            <label>确认密码</label>
            <input type="password" name="admin_password_confirmation" required minlength="8">
            <label class="check">
                <input type="checkbox" name="seed_demo" value="1" @checked(old('seed_demo', true))>
                <span>导入演示数据（栏目/示例内容；演示超管仍为 admin@example.com / Admin@12345）</span>
            </label>
            <button class="btn" type="submit">开始安装</button>
        </form>
    @else
        <p class="bad">请先修复上述环境问题后再安装。</p>
    @endif
</div>
</body>
</html>
