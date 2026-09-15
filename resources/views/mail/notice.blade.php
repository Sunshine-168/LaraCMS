@extends('mail.layout', ['heading' => $heading, 'siteName' => $siteName, 'actionUrl' => $url ?? '', 'actionText' => $actionText ?? ''])
@section('content')
    <p>{{ $intro }}</p>
    @if(!empty($lines) && is_array($lines))
        <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:12px 0;font-size:14px">
            @foreach($lines as $label => $value)
                <tr>
                    <td style="padding:6px 0;color:#64748b;width:28%;vertical-align:top">{{ $label }}</td>
                    <td style="padding:6px 0;vertical-align:top;white-space:pre-wrap">{{ is_array($value) ? implode('、', $value) : $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if(!empty($body))
        <p style="white-space:pre-wrap">{{ $body }}</p>
    @endif
@endsection
