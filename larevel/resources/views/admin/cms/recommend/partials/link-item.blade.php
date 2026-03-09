<div style="padding:16px;">
    <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <div>
            <label>链接</label>
            <input name="link_url[]" value="{{ $item['url'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        </div>
        <div>
            <label>标题</label>
            <input name="link_title[]" value="{{ $item['title'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        </div>
        <div>
            <label>排序</label>
            <input name="link_weigh[]" type="number" value="{{ $item['weigh'] ?? 0 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        </div>
        <div>
            <label>新窗口打开</label>
            <select name="link_new_window[]" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                <option value="0" @selected((string) ($item['new_window'] ?? '0') === '0')>否</option>
                <option value="1" @selected((string) ($item['new_window'] ?? '0') === '1')>是</option>
            </select>
        </div>
        <div style="grid-column:1 / -1;">
            <label>描述</label>
            <textarea name="link_notes[]" style="width:100%;min-height:80px;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ $item['notes'] ?? '' }}</textarea>
        </div>
    </div>
    <div style="margin-top:12px;">
        <button type="button" class="btn btn-secondary" onclick="removeRecommendItem(this)">删除这一项</button>
    </div>
</div>
