<?php

namespace App\Services\Cms\Tags;

use Illuminate\Support\HtmlString;

class MapTag
{
    /**
     * 简易地图嵌入（OpenStreetMap）
     *
     * @param  array<string, mixed>  $options  address|point(lng,lat)|width|height
     */
    public function render(array $options = []): HtmlString
    {
        $point = (string) ($options['point'] ?? '');
        $address = (string) ($options['address'] ?? '');
        // 兼容字段存盘：lng,lat|地址
        if ($point !== '' && str_contains($point, '|')) {
            [$coordPart, $addrPart] = array_pad(explode('|', $point, 2), 2, '');
            $point = trim($coordPart);
            if ($address === '' && trim($addrPart) !== '') {
                $address = trim($addrPart);
            }
        }
        $width = e((string) ($options['width'] ?? '100%'));
        $height = e((string) ($options['height'] ?? '320'));
        $title = e((string) ($options['title'] ?? $address ?: cms_ui('map')));

        if ($point && str_contains($point, ',')) {
            [$lng, $lat] = array_map('trim', explode(',', $point, 2));
        } else {
            $lng = (string) (\App\Models\Cms\Setting::getValue('map_default_lng', '116.397') ?: '116.397');
            $lat = (string) (\App\Models\Cms\Setting::getValue('map_default_lat', '39.908') ?: '39.908');
            if ($address === '' && $point !== '') {
                $address = $point;
            }
        }

        $bboxPad = 0.02;
        $left = (float) $lng - $bboxPad;
        $right = (float) $lng + $bboxPad;
        $top = (float) $lat + $bboxPad;
        $bottom = (float) $lat - $bboxPad;
        $src = 'https://www.openstreetmap.org/export/embed.html?bbox='
            .rawurlencode("{$left},{$bottom},{$right},{$top}")
            .'&layer=mapnik&marker='.rawurlencode("{$lat},{$lng}");

        $html = '<div class="cms-map" id="'.e((string) ($options['htmlid'] ?? 'cms-map')).'">';
        if ($address) {
            $html .= '<p class="cms-map-address">'.$title.' — '.e($address).'</p>';
        }
        $html .= '<iframe title="'.$title.'" src="'.e($src).'" style="width:'.$width.';height:'.$height.'px;border:0;border-radius:8px" loading="lazy"></iframe>';
        $html .= '</div>';

        return new HtmlString($html);
    }
}
