@extends('mail.layout', ['heading' => $heading, 'siteName' => $siteName, 'actionUrl' => $url, 'actionText' => $actionText ?? cms_ui('mail_reset_action')])
@section('content')
    <p>{{ $body ?? cms_ui('mail_reset_body', ['audience' => $audience ?? cms_ui('mail_reset_audience')]) }}</p>
@endsection
