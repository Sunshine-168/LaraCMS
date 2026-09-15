@extends('mail.layout', ['heading' => $heading, 'siteName' => $siteName, 'actionUrl' => $url, 'actionText' => '打开重置链接'])
@section('content')
    <p>你正在重置{{ $audience ?? '账号' }}密码。请在 1 小时内打开下面的按钮。如果不是你本人操作，请忽略这封信。</p>
@endsection
