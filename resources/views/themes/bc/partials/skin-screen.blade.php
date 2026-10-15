@php
    $hero = $hero ?? false;
    $nav = $skin['nav'] ?? [];
@endphp
<div class="skin-shot tone-{{ $skin['tone'] }}{{ $hero ? ' is-hero' : '' }}">
    <div class="skin-chrome"><i></i><i></i><i></i><em>{{ $skin['name'] }}</em></div>
    <div class="skin-stage">
        <div class="skin-nav">
            <strong>{{ $skin['mark'] ?? mb_substr($skin['name'], 0, 2) }}</strong>
            @foreach($nav as $item)
                <span @if($loop->first) class="is-on" @endif>{{ $item }}</span>
            @endforeach
            <em>{{ cms_t('登录', 'Log in') }}</em>
        </div>

        @if($skin['layout'] === 'odds')
            <div class="skin-banner" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                <small>{{ $skin['board'][0]['league'] ?? '' }} · {{ $skin['kind_label'] }}</small>
                <strong>{{ ($skin['board'][0]['home'] ?? '') }} <i>vs</i> {{ ($skin['board'][0]['away'] ?? '') }}</strong>
            </div>
            <div class="skin-board">
                @foreach($skin['board'] ?? [] as $row)
                    @php $labels = $row['labels'] ?? [cms_t('主', '1'), cms_t('和', 'X'), cms_t('客', '2')]; @endphp
                    <div class="skin-match">
                        <div class="skin-match-top">
                            @if(!empty($row['live']))<b>{{ cms_t('滚球', 'Live') }}</b>@else<span>{{ cms_t('未开', 'Later') }}</span>@endif
                            <span>{{ $row['league'] }}</span>
                            <span>{{ $row['time'] ?? '' }}</span>
                        </div>
                        <div class="skin-match-line">
                            <span>{{ $row['home'] }}</span>
                            <strong>{{ $row['score'] ?? 'VS' }}</strong>
                            <span>{{ $row['away'] }}</span>
                        </div>
                        <div class="skin-odds">
                            @foreach($row['odds'] as $index => $odd)
                                <em><small>{{ $labels[$index] ?? '' }}</small>{{ $odd }}</em>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($skin['layout'] === 'lobby')
            <div class="skin-lobby">
                <div class="skin-feature" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                    <small><i class="skin-live"></i> LIVE</small>
                    <strong>{{ $skin['tables'][0]['name'] ?? $skin['name'] }}</strong>
                    <span>{{ cms_t('限红', 'Limit') }} {{ $skin['tables'][0]['limit'] ?? '' }}</span>
                    <div class="skin-road" aria-hidden="true"><i class="p"></i><i class="bk"></i><i class="p"></i><i class="p"></i><i class="t"></i><i class="bk"></i><i class="p"></i><i class="bk"></i></div>
                </div>
                <div class="skin-side">
                    @foreach(array_slice($skin['tables'] ?? [], 1) as $table)
                        <div>
                            <b><i class="skin-live"></i>{{ $table['name'] }}</b>
                            <span>{{ cms_t('限红', 'Limit') }} {{ $table['limit'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($skin['layout'] === 'slip')
            <div class="skin-slip">
                <div class="skin-leagues">
                    @foreach($skin['leagues'] ?? [] as $league)
                        <span @if($loop->first) class="is-on" @endif>{{ $league }}</span>
                    @endforeach
                </div>
                <div>
                    <div class="skin-banner is-short" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                        <small>{{ $skin['board'][0]['league'] ?? '' }}</small>
                        <strong>{{ $skin['board'][0]['home'] ?? '' }} <i>vs</i> {{ $skin['board'][0]['away'] ?? '' }}</strong>
                    </div>
                    <div class="skin-odds">
                        @foreach($skin['board'][0]['odds'] ?? [] as $index => $odd)
                            <em><small>{{ ($skin['board'][0]['labels'] ?? [cms_t('主', '1'), cms_t('和', 'X'), cms_t('客', '2')])[$index] ?? '' }}</small>{{ $odd }}</em>
                        @endforeach
                    </div>
                </div>
                <div class="skin-betslip">
                    <b>{{ cms_t('注单', 'Slip') }}</b>
                    <span>{{ $skin['board'][0]['home'] ?? '' }}</span>
                    <em>{{ $skin['board'][0]['odds'][0] ?? '' }}</em>
                    <i>100</i>
                    <strong>{{ cms_t('投注', 'Bet') }}</strong>
                </div>
            </div>
        @elseif($skin['layout'] === 'tables')
            <div class="skin-tables" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                @foreach($skin['tables'] ?? [] as $table)
                    <div>
                        <small><i class="skin-live"></i> LIVE</small>
                        <b>{{ $table['name'] }}</b>
                        <span>{{ $table['limit'] }}</span>
                    </div>
                @endforeach
            </div>
        @elseif($skin['layout'] === 'hall')
            <div class="skin-hall">
                @foreach($skin['doors'] ?? [] as $door)
                    <div style="--shot:url('{{ asset('themes/bc/'.$door['img']) }}')">
                        <strong>{{ $door['name'] }}</strong>
                        <span>{{ $door['meta'] }}</span>
                    </div>
                @endforeach
            </div>
        @elseif($skin['layout'] === 'rail')
            <div class="skin-jackpot"><small>{{ cms_t('奖池', 'Jackpot') }}</small><strong>{{ $skin['jackpot'] ?? '' }}</strong></div>
            <div class="skin-rail" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                @foreach($skin['games'] ?? [] as $game)
                    <i><b>{{ $game }}</b></i>
                @endforeach
            </div>
        @elseif($skin['layout'] === 'rooms')
            <div class="skin-rooms">
                @foreach($skin['rooms'] ?? [] as $room)
                    <div>
                        <b>{{ $room['name'] }}</b>
                        <span>{{ $room['meta'] }}</span>
                        <em>{{ $room['go'] }}</em>
                    </div>
                @endforeach
            </div>
        @elseif($skin['layout'] === 'draw')
            <div class="skin-draw">
                <div class="skin-clock"><small>{{ cms_t('下期', 'Next') }}</small><strong>{{ $skin['clock'] ?? '' }}</strong></div>
                <div class="skin-balls">
                    @foreach($skin['balls'] ?? [] as $ball)
                        <i>{{ $ball }}</i>
                    @endforeach
                </div>
                <div class="skin-plays">
                    @foreach($skin['plays'] ?? [] as $play)
                        <span>{{ $play }}</span>
                    @endforeach
                </div>
            </div>
        @else
            <div class="skin-banner is-short" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                <small>{{ $skin['kind_label'] }}</small>
                <strong>{{ $skin['name'] }}</strong>
            </div>
            <div class="skin-tiles" style="--shot:url('{{ asset('themes/bc/'.$skin['img']) }}')">
                @foreach($skin['games'] ?? [] as $game)
                    <i><b>{{ $game }}</b><small>{{ 800 + ($loop->index * 137) }} {{ cms_t('在玩', 'playing') }}</small></i>
                @endforeach
            </div>
        @endif
    </div>
</div>
