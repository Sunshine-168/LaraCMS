@php
    $skins = [
        [
            'id' => 'harbor', 'name' => '星港真人', 'kind' => 'live', 'kind_label' => '真人', 'tag' => '旗舰', 'fresh' => true,
            'note' => '暗金桌台厅。荷官、限红、路单在同一屏，适合当主站。',
            'layout' => 'lobby', 'img' => 'stock/roulette.jpg', 'tone' => 'champagne',
            'nav' => ['真人', '体育', '电子', '优惠'],
            'tables' => [
                ['name' => '极速百家乐', 'limit' => '20 – 50,000', 'hot' => true],
                ['name' => '轮盘', 'limit' => '10 – 20,000', 'hot' => false],
                ['name' => '龙虎', 'limit' => '20 – 30,000', 'hot' => false],
            ],
        ],
        [
            'id' => 'obsidian', 'name' => '曜石体育', 'kind' => 'sports', 'kind_label' => '体育', 'tag' => '推荐', 'fresh' => false,
            'note' => '赛事列表。滚球置顶，一行一场，盘口在右侧。',
            'layout' => 'odds', 'img' => 'stock/stadium.jpg', 'tone' => 'obsidian',
            'nav' => ['滚球', '今日', '早盘', '串关'],
            'board' => [
                ['live' => true, 'league' => '英超', 'time' => '67′', 'home' => '曼城', 'away' => '阿森纳', 'score' => '1 : 0', 'odds' => ['1.86', '3.40', '4.20']],
                ['live' => false, 'league' => '西甲', 'time' => '21:00', 'home' => '皇马', 'away' => '巴萨', 'score' => 'VS', 'odds' => ['2.05', '3.55', '3.30']],
            ],
        ],
        [
            'id' => 'aurora', 'name' => '极光电子', 'kind' => 'slots', 'kind_label' => '电子', 'tag' => '最新', 'fresh' => true,
            'note' => '深色游戏墙。封面、名字、在玩人数，按馆筛选。',
            'layout' => 'tiles', 'img' => 'stock/slots.jpg', 'tone' => 'violet',
            'nav' => ['热门', 'PG', 'PP', '最新'],
            'games' => ['星河', '黑金', '极光', '夜航', '红宝石', '深海'],
        ],
        [
            'id' => 'crimson', 'name' => '赤曜综合', 'kind' => 'live', 'kind_label' => '综合', 'tag' => '推荐', 'fresh' => false,
            'note' => '四桌同屏。每张卡是一张桌，不是一张大图加两个侧栏。',
            'layout' => 'tables', 'img' => 'stock/neon.jpg', 'tone' => 'crimson',
            'nav' => ['百家乐', '轮盘', '骰宝', '牛牛'],
            'tables' => [
                ['name' => '百家乐 A', 'limit' => '50 – 100,000'],
                ['name' => '骰宝', 'limit' => '10 – 10,000'],
                ['name' => '牛牛', 'limit' => '20 – 20,000'],
                ['name' => '轮盘', 'limit' => '10 – 20,000'],
            ],
        ],
        [
            'id' => 'north', 'name' => '北境体育', 'kind' => 'sports', 'kind_label' => '体育', 'tag' => '多语言', 'fresh' => false,
            'note' => '左侧联赛，右侧注单。和曜石的赛事列表不是同一屏。',
            'layout' => 'slip', 'img' => 'stock/hero.jpg', 'tone' => 'arctic',
            'leagues' => ['英超', '欧冠', 'NBA', '西甲'],
            'nav' => ['Live', 'Today', '早盘', '中 / EN'],
            'board' => [
                ['live' => true, 'league' => '欧冠', 'time' => '54′', 'home' => '拜仁', 'away' => '巴黎', 'score' => '2 : 1', 'odds' => ['1.92', '3.60', '3.70']],
                ['live' => false, 'league' => 'NBA', 'time' => '08:30', 'home' => '湖人', 'away' => '凯尔特人', 'score' => 'VS', 'labels' => ['主', '让', '客'], 'odds' => ['1.78', '-2.5', '2.04']],
            ],
        ],
        [
            'id' => 'tide', 'name' => '潮汐棋牌', 'kind' => 'card', 'kind_label' => '棋牌', 'tag' => '最新', 'fresh' => true,
            'note' => '房间列表。底注、座位、进入，按行排，不是封面墙。',
            'layout' => 'rooms', 'img' => 'stock/cards.jpg', 'tone' => 'emerald',
            'nav' => ['德州', '牛牛', '金花', '麻将'],
            'rooms' => [
                ['name' => '德州 5/10', 'meta' => '6/9 座', 'go' => '进入'],
                ['name' => '牛牛 20', 'meta' => '4/6 座', 'go' => '进入'],
                ['name' => '金花 10', 'meta' => '5/6 座', 'go' => '进入'],
                ['name' => '麻将', 'meta' => '2/4 座', 'go' => '进入'],
            ],
        ],
        [
            'id' => 'cloud', 'name' => '云栖电子', 'kind' => 'slots', 'kind_label' => '电子', 'tag' => '多语言', 'fresh' => false,
            'note' => '奖池横条加一排游戏。和极光的六宫格不是同一屏。',
            'layout' => 'rail', 'img' => 'stock/floor.jpg', 'tone' => 'royal',
            'nav' => ['奖池', '热门', 'PG', '收藏'],
            'jackpot' => '12,480,600',
            'games' => ['云栖', '莲花', '金龙', '月相'],
        ],
        [
            'id' => 'ink', 'name' => '墨金彩票', 'kind' => 'card', 'kind_label' => '彩票', 'tag' => '最新', 'fresh' => true,
            'note' => '倒计时、上期号码、大小单双。不是游戏封面。',
            'layout' => 'draw', 'img' => 'stock/dice.jpg', 'tone' => 'ink',
            'nav' => ['快开', '时时', '六合', '官网'],
            'clock' => '01:24',
            'balls' => ['07', '19', '23', '31', '42'],
            'plays' => ['大', '小', '单', '双'],
        ],
        [
            'id' => 'bay', 'name' => '夜湾综合', 'kind' => 'live', 'kind_label' => '综合', 'tag' => '多语言', 'fresh' => false,
            'note' => '三个入口：真人、体育、电子。第一次开站用这一屏。',
            'layout' => 'hall', 'img' => 'stock/city.jpg', 'tone' => 'night',
            'nav' => ['首页', '真人', '体育', '电子'],
            'doors' => [
                ['name' => '真人', 'meta' => '24 桌', 'img' => 'stock/roulette.jpg'],
                ['name' => '体育', 'meta' => '128 场', 'img' => 'stock/stadium.jpg'],
                ['name' => '电子', 'meta' => '860 款', 'img' => 'stock/slots.jpg'],
            ],
        ],
    ];
    if (\App\Support\Locale::isEnglish()) {
        $english = [
            'harbor' => [
                'name' => 'Harbor Live', 'mark' => 'HB', 'kind_label' => 'Live', 'tag' => 'Flagship',
                'note' => 'A dark gold table hall. Dealer, limits, and the road on one screen. Fits a main site.',
                'nav' => ['Live', 'Sports', 'Slots', 'Promos'],
                'tables' => [
                    ['name' => 'Speed baccarat'],
                    ['name' => 'Roulette'],
                    ['name' => 'Dragon Tiger'],
                ],
            ],
            'obsidian' => [
                'name' => 'Obsidian Sports', 'mark' => 'OB', 'kind_label' => 'Sports', 'tag' => 'Featured',
                'note' => 'A match list. In-play stays on top, one match per row, odds on the right.',
                'nav' => ['In-play', 'Today', 'Early', 'Parlay'],
                'board' => [
                    ['league' => 'Premier League', 'home' => 'Man City', 'away' => 'Arsenal'],
                    ['league' => 'La Liga', 'home' => 'Real Madrid', 'away' => 'Barcelona'],
                ],
            ],
            'aurora' => [
                'name' => 'Aurora Slots', 'mark' => 'AU', 'kind_label' => 'Slots', 'tag' => 'New',
                'note' => 'A dark game wall. Cover, name, and players, filtered by studio.',
                'nav' => ['Hot', 'PG', 'PP', 'New'],
                'games' => ['Galaxy', 'Black gold', 'Aurora', 'Night', 'Ruby', 'Deep'],
            ],
            'crimson' => [
                'name' => 'Crimson Hall', 'mark' => 'CR', 'kind_label' => 'Mixed', 'tag' => 'Featured',
                'note' => 'Four tables on one screen. Each card is a table, not one large image with two sidebars.',
                'nav' => ['Baccarat', 'Roulette', 'Sic bo', 'Niu niu'],
                'tables' => [
                    ['name' => 'Baccarat A'],
                    ['name' => 'Sic bo'],
                    ['name' => 'Niu niu'],
                    ['name' => 'Roulette'],
                ],
            ],
            'north' => [
                'name' => 'North Sports', 'mark' => 'NT', 'kind_label' => 'Sports', 'tag' => 'Multilingual',
                'note' => 'Leagues on the left, the slip on the right. Not the same screen as Obsidian’s match list.',
                'nav' => ['Live', 'Today', 'Early', 'ZH / EN'],
                'leagues' => ['Premier League', 'UCL', 'NBA', 'La Liga'],
                'board' => [
                    ['league' => 'UCL', 'home' => 'Bayern', 'away' => 'PSG', 'labels' => ['Home', 'Line', 'Away']],
                    ['league' => 'NBA', 'home' => 'Lakers', 'away' => 'Celtics', 'labels' => ['Home', 'Line', 'Away']],
                ],
            ],
            'tide' => [
                'name' => 'Tide Cards', 'mark' => 'TD', 'kind_label' => 'Cards', 'tag' => 'New',
                'note' => 'A room list. Stakes, seats, and enter, in rows, not a cover wall.',
                'nav' => ['Hold’em', 'Niu niu', 'Goldflower', 'Mahjong'],
                'rooms' => [
                    ['name' => 'Hold’em 5/10', 'meta' => '6/9 seats', 'go' => 'Enter'],
                    ['name' => 'Niu niu 20', 'meta' => '4/6 seats', 'go' => 'Enter'],
                    ['name' => 'Goldflower 10', 'meta' => '5/6 seats', 'go' => 'Enter'],
                    ['name' => 'Mahjong', 'meta' => '2/4 seats', 'go' => 'Enter'],
                ],
            ],
            'cloud' => [
                'name' => 'Cloud Slots', 'mark' => 'CL', 'kind_label' => 'Slots', 'tag' => 'Multilingual',
                'note' => 'A jackpot bar and a row of games. Not the same screen as Aurora’s six-tile grid.',
                'nav' => ['Jackpot', 'Hot', 'PG', 'Saved'],
                'games' => ['Cloud', 'Lotus', 'Dragon', 'Moon'],
            ],
            'ink' => [
                'name' => 'Ink Lottery', 'mark' => 'IN', 'kind_label' => 'Lottery', 'tag' => 'New',
                'note' => 'A countdown, the last draw, and big, small, odd, even. Not a game cover.',
                'nav' => ['Fast', 'Frequent', 'Mark six', 'Official'],
                'plays' => ['Big', 'Small', 'Odd', 'Even'],
            ],
            'bay' => [
                'name' => 'Night Bay', 'mark' => 'NB', 'kind_label' => 'Mixed', 'tag' => 'Multilingual',
                'note' => 'Three doors: live, sports, and slots. Use this screen for a first launch.',
                'nav' => ['Home', 'Live', 'Sports', 'Slots'],
                'doors' => [
                    ['name' => 'Live', 'meta' => '24 tables'],
                    ['name' => 'Sports', 'meta' => '128 events'],
                    ['name' => 'Slots', 'meta' => '860 titles'],
                ],
            ],
        ];
        foreach ($skins as $i => $skin) {
            $over = $english[$skin['id']] ?? null;
            if (! $over) {
                continue;
            }
            foreach ($over as $key => $value) {
                if (is_array($value) && isset($skin[$key]) && is_array($skin[$key]) && isset($skin[$key][0]) && is_array($skin[$key][0]) && isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $rowIndex => $row) {
                        if (isset($skin[$key][$rowIndex]) && is_array($skin[$key][$rowIndex])) {
                            $skin[$key][$rowIndex] = array_merge($skin[$key][$rowIndex], $row);
                        }
                    }
                } else {
                    $skin[$key] = $value;
                }
            }
            $skins[$i] = $skin;
        }
    }
    $pick = $only ?? null;
    if ($pick) {
        $skins = array_values(array_filter($skins, fn ($skin) => $skin['id'] === $pick));
    }
    $shotOnly = $shotOnly ?? false;
@endphp

@foreach($skins as $skin)
    @if($shotOnly)
        @include('themes.bc.partials.skin-screen', ['skin' => $skin, 'hero' => true])
    @else
        <article class="skin" data-kind="{{ $skin['kind'] }}" id="skin-{{ $skin['id'] }}">
            @include('themes.bc.partials.skin-screen', ['skin' => $skin, 'hero' => false])
            <div class="skin-body">
                <div class="skin-name">
                    <h3>{{ $skin['name'] }}</h3>
                    <span>{{ $skin['tag'] }}</span>
                </div>
                <p>{{ $skin['note'] }}</p>
                <div class="skin-ends">
                    <button type="button" data-open="front">{{ cms_t('前台', 'Front') }}</button>
                    <button type="button" data-open="admin">{{ cms_t('后台', 'Admin') }}</button>
                    <button type="button" data-open="agent">{{ cms_t('代理', 'Agent') }}</button>
                </div>
            </div>
        </article>
    @endif
@endforeach
