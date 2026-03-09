@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ route('admin.attachments.water') }}">
            @csrf

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>水印功能</label>
                    <select name="water_on" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected(($sdata['water_on'] ?? '0') === '1')>开启</option>
                        <option value="0" @selected(($sdata['water_on'] ?? '0') === '0')>关闭</option>
                    </select>
                </div>
                <div>
                    <label>水印类型</label>
                    <select name="water_type" id="water-type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected(($sdata['water_type'] ?? '1') === '1')>图片</option>
                        <option value="2" @selected(($sdata['water_type'] ?? '1') === '2')>文字</option>
                    </select>
                </div>
                <div>
                    <label>图片宽度</label>
                    <input name="water_width" type="number" value="{{ $sdata['water_width'] ?? 100 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>图片高度</label>
                    <input name="water_height" type="number" value="{{ $sdata['water_height'] ?? 50 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
            </div>

            <div id="water-image-fields" style="margin-top:16px;{{ ($sdata['water_type'] ?? '1') === '2' ? 'display:none;' : '' }}">
                <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div>
                        <label>水印图片</label>
                        <div style="display:flex;gap:8px;">
                            <input id="water-image-input" name="water_img" value="{{ $sdata['water_img'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                            <button type="button" class="btn btn-secondary" onclick="openWaterImageSelector()">选择</button>
                        </div>
                    </div>
                    <div>
                        <label>水印透明度</label>
                        <input name="water_img_opacity" type="number" min="0" max="100" value="{{ $sdata['water_img_opacity'] ?? 60 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                </div>
            </div>

            <div id="water-text-fields" style="margin-top:16px;{{ ($sdata['water_type'] ?? '1') === '1' ? 'display:none;' : '' }}">
                <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div>
                        <label>文字</label>
                        <input name="water_text" value="{{ $sdata['water_text'] ?? 'HkCms' }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                    <div>
                        <label>文字大小</label>
                        <input name="water_text_size" type="number" value="{{ $sdata['water_text_size'] ?? 16 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                    <div>
                        <label>文字颜色</label>
                        <input name="water_text_color" value="{{ $sdata['water_text_color'] ?? '#00000042' }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    </div>
                </div>
            </div>

            <div style="margin-top:16px;">
                <label>水印位置</label>
                <div class="grid grid-3" style="margin-top:8px;">
                    @foreach([
                        1 => '顶部居左', 2 => '顶部居中', 3 => '顶部居右',
                        4 => '中部居左', 5 => '中部居中', 6 => '中部居右',
                        7 => '底部居左', 8 => '底部居中', 9 => '底部居右',
                    ] as $value => $label)
                        <label style="display:flex;align-items:center;gap:6px;">
                            <input type="radio" name="water_img_position" value="{{ $value }}" @checked((int) ($sdata['water_img_position'] ?? 7) === $value)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">保存设置</button>
                <a href="{{ route('admin.attachments.index') }}" class="btn btn-secondary">返回附件库</a>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const typeSelect = document.getElementById('water-type');
            const imageFields = document.getElementById('water-image-fields');
            const textFields = document.getElementById('water-text-fields');

            function refresh() {
                const isImage = typeSelect.value === '1';
                imageFields.style.display = isImage ? '' : 'none';
                textFields.style.display = isImage ? 'none' : '';
            }

            typeSelect.addEventListener('change', refresh);
            refresh();
        })();

        function openWaterImageSelector() {
            const win = window.open(@json(route('admin.attachments.select', ['multiple' => 'false', 'mimetype' => 'image/'])), '_blank', 'width=1100,height=760');
            window.addEventListener('message', function handler(event) {
                if (!event.data || event.data.type !== 'attachment:selected') {
                    return;
                }
                document.getElementById('water-image-input').value = (event.data.paths || [])[0] || '';
                window.removeEventListener('message', handler);
                if (win && !win.closed) {
                    win.close();
                }
            });
        }
    </script>
@endsection
