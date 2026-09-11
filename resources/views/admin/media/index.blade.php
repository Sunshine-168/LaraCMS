@extends('admin.layout')

@section('title', '媒体')

@section('content')
    <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" style="margin-bottom:20px;">
        @csrf
        <label>上传文件</label>
        <input type="file" name="file" required>
        <label>Alt</label>
        <input type="text" name="alt">
        <p><button class="btn" type="submit">上传</button></p>
    </form>

    <table>
        <thead>
        <tr><th>预览</th><th>文件名</th><th>大小</th><th>URL</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($media as $item)
            <tr>
                <td>
                    @if(str_starts_with((string) $item->mime, 'image/'))
                        <img src="{{ $item->url() }}" alt="{{ $item->alt }}" style="max-width:80px;max-height:60px;">
                    @else
                        —
                    @endif
                </td>
                <td>{{ $item->filename }}</td>
                <td>{{ number_format($item->size / 1024, 1) }} KB</td>
                <td><a href="{{ $item->url() }}" target="_blank">打开</a></td>
                <td>
                    <form action="{{ route('admin.media.destroy', $item) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $media->links() }}
@endsection
