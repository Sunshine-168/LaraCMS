@extends('errors.bare')

@section('title', '服务器出错了')

@section('content')
    <p>500</p>
    <h1>{{ $siteName ?? config('app.name') }}</h1>
    <p>服务器暂时出了点问题，请稍后再试。</p>
    <p><a href="{{ url('/') }}">回首页</a></p>
@endsection
