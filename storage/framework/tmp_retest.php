<?php

$base = 'http://127.0.0.1:8010';
$cookie = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cms_t_'.uniqid().'.txt';
$fail = [];
$ok = 0;

function req(string $method, string $path, array $fields = [], bool $follow = true): array
{
    global $base, $cookie;
    $ch = curl_init($base.$path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 6,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERAGENT => 'CMS-Retest/1.0',
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $hs = (int) ($info['header_size'] ?? 0);
    $body = $raw === false ? $err : substr((string) $raw, $hs);

    return [
        'code' => (int) ($info['http_code'] ?? 0),
        'body' => $body,
        'redirect' => (string) ($info['redirect_url'] ?? ''),
        'final' => (string) ($info['url'] ?? ''),
    ];
}

function csrfCaptcha(string $html): array
{
    $token = '';
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)
        || preg_match('/value="([^"]+)"\s+name="_token"/', $html, $m)) {
        $token = $m[1];
    }
    $sum = null;
    if (preg_match('/验证码[：:\s]*(\d+)\s*\+\s*(\d+)/u', $html, $m)) {
        $sum = ((int) $m[1]) + ((int) $m[2]);
    }

    return [$token, $sum];
}

function bad(string $html): ?string
{
    if (str_contains($html, 'Whoops') || str_contains($html, 'Server Error')) {
        return 'exception';
    }
    if (preg_match('/SQLSTATE|syntax error, unexpected|Undefined variable|View \[.*\] not found/i', $html)) {
        return 'php-error';
    }

    return null;
}

function check(string $label, array $r, array $codes = [200]): void
{
    global $fail, $ok;
    $b = bad($r['body'] ?? '');
    if ($b) {
        $fail[] = "$label HTTP {$r['code']} ($b)";

        return;
    }
    if (! in_array($r['code'], $codes, true)) {
        $fail[] = "$label HTTP {$r['code']} want ".implode('/', $codes);

        return;
    }
    $ok++;
}

function has(string $label, array $r, string $n): void
{
    global $fail, $ok;
    if (! str_contains($r['body'] ?? '', $n)) {
        $fail[] = "$label missing $n";

        return;
    }
    $ok++;
}

function lacks(string $label, array $r, string $n): void
{
    global $fail, $ok;
    if (str_contains($r['body'] ?? '', $n)) {
        $fail[] = "$label has $n";

        return;
    }
    $ok++;
}

$front = [
    '/', '/en',
    '/news', '/about', '/products', '/jobs', '/downloads',
    '/nav-tools', '/nav-dev', '/sources', '/blog', '/comics', '/matches', '/chapters',
    '/news/cms-launch', '/products/smart-gateway', '/jobs/php-engineer',
    '/blog/hello-blog', '/sources/shop-pro', '/comics/star-traveler',
    '/matches/sample-derby', '/e/1', '/e/2', '/e/3',
    '/search', '/search?q=CMS', '/search?q=Windows',
    '/en/search', '/en/search?q=CMS', '/en/news', '/en/news/cms-launch', '/en/downloads',
    '/tag/laravel', '/en/tag/laravel',
    '/rss.xml', '/en/rss.xml', '/sitemap.xml', '/en/sitemap.xml',
    '/member/login', '/member/register', '/member/forgot',
    '/en/member/login', '/en/member/register', '/en/member/forgot',
];
foreach ($front as $p) {
    check($p, req('GET', $p));
}

has('home search', req('GET', '/'), '搜索');
has('en Search', req('GET', '/en'), 'Search');
has('en Member', req('GET', '/en'), 'Member');
lacks('guest search gated', req('GET', '/search?q=Windows'), '客户端 Windows 版');
has('search public', req('GET', '/search?q=CMS'), '综合 CMS 上线');
lacks('rss gated', req('GET', '/rss.xml'), '客户端 Windows 版');
has('en login', req('GET', '/en/member/login'), '/en/member/login');
check('guest profile', req('GET', '/member/profile', [], false), [302]);
check('guest download', req('GET', '/download/4', [], false), [302]);
check('gated show', req('GET', '/downloads/client-windows', [], false), [302]);
$go = req('GET', '/go/5', [], false);
check('go/5', $go, [302]);
if (! str_contains($go['redirect'], 'github.com')) {
    $fail[] = 'go/5 '.$go['redirect'];
} else {
    $ok++;
}

foreach ([
    '/api/site/config', '/api/category/list', '/api/category/tree', '/api/category/1',
    '/api/content/list', '/api/content/2', '/api/search?q=CMS', '/api/search?q=Windows',
    '/api/tag/list', '/api/tag/laravel', '/api/recommend?name=home_banner',
] as $p) {
    check($p, req('GET', $p));
}
check('api gated', req('GET', '/api/content/4'), [403]);
$list = req('GET', '/api/content/list');
if (preg_match('/"id"\s*:\s*4\b/', $list['body'])) {
    $fail[] = 'api list id 4';
} else {
    $ok++;
}

$article = req('GET', '/news/cms-launch');
[$t, $sum] = csrfCaptcha($article['body']);
if ($t && $sum !== null) {
    check('comment', req('POST', '/comment/2', [
        '_token' => $t, 'nickname' => '复测', 'body' => '复测评论 '.date('His'), 'captcha' => (string) $sum,
    ], false), [302]);
} else {
    $fail[] = 'comment captcha';
}

[$t, $sum] = csrfCaptcha(req('GET', '/')['body']);
if ($t && $sum !== null) {
    check('contact form', req('POST', '/form/contact', [
        '_token' => $t, 'name' => '复测', 'email' => 'retest@example.com', 'message' => '复测留言', 'captcha' => (string) $sum,
    ], false), [302, 200]);
}

$ml = req('GET', '/member/login');
[$t, $sum] = csrfCaptcha($ml['body']);
if ($t && $sum !== null) {
    check('member login', req('POST', '/member/login', [
        '_token' => $t, 'email' => 'member@example.com', 'password' => 'Member@12345', 'captcha' => (string) $sum,
    ], false), [302]);
    check('profile', req('GET', '/member/profile'));
    has('hub', req('GET', '/member/profile'), '会员中心');
    check('entries', req('GET', '/member/entries'));
    check('entries create', req('GET', '/member/entries/create'));
    $md = req('GET', '/download/4', [], false);
    if ($md['code'] === 302 && str_contains($md['redirect'], 'member/login')) {
        $fail[] = 'member download still login';
    } else {
        $ok++;
    }
    check('gated show member', req('GET', '/downloads/client-windows'));
} else {
    $fail[] = 'member captcha';
}

$al = req('GET', '/admin/login');
check('admin login page', $al);
[$t, $sum] = csrfCaptcha($al['body']);
if ($t && $sum !== null) {
    check('admin login', req('POST', '/admin/login', [
        '_token' => $t, 'email' => 'admin@example.com', 'password' => 'Admin@12345', 'captcha' => (string) $sum,
    ], false), [302]);
} else {
    $fail[] = 'admin captcha';
}

$admin = [
    '/admin', '/admin/dashboard', '/admin/quick-panels',
    '/admin/entries', '/admin/entries/create', '/admin/entries/1/edit', '/admin/entries/2/edit', '/admin/entries/4/edit',
    '/admin/recycle',
    '/admin/categories', '/admin/categories/create', '/admin/categories/1/edit',
    '/admin/tags', '/admin/tags/create',
    '/admin/flags', '/admin/flags/create',
    '/admin/recommends', '/admin/recommends/create',
    '/admin/models', '/admin/models/create', '/admin/models/import', '/admin/models/1/edit',
    '/admin/media', '/admin/media/json',
    '/admin/menus', '/admin/menus/create',
    '/admin/forms', '/admin/forms/create',
    '/admin/form-submissions',
    '/admin/comments',
    '/admin/redirects', '/admin/redirects/create',
    '/admin/collectors', '/admin/collectors/create', '/admin/collectors/logs',
    '/admin/members', '/admin/members/create',
    '/admin/member-groups', '/admin/member-groups/create',
    '/admin/html-cache',
    '/admin/sites', '/admin/sites/create',
    '/admin/settings', '/admin/cache',
    '/admin/locales', '/admin/locales/create',
    '/admin/logs',
    '/admin/admin-users', '/admin/admin-users/create',
    '/admin/roles', '/admin/roles/create',
    '/admin/stats', '/admin/stats/spiders', '/admin/stats/logs',
];
foreach ($admin as $p) {
    check($p, req('GET', $p));
}

has('rec help', req('GET', '/admin/recommends/create'), '@cmsAdv');
has('menu help', req('GET', '/admin/menus/create'), '@cmsMenu');
has('form help', req('GET', '/admin/forms'), '@cmsForm');
has('sidebar', req('GET', '/admin'), '内容管理');

if (preg_match_all('/admin\/recommends\/(\d+)\/edit/', req('GET', '/admin/recommends')['body'], $m)) {
    check('recommend edit', req('GET', '/admin/recommends/'.$m[1][0].'/edit'));
}
if (preg_match_all('/admin\/menus\/(\d+)\/edit/', req('GET', '/admin/menus')['body'], $m)) {
    check('menu edit', req('GET', '/admin/menus/'.$m[1][0].'/edit'));
}
if (preg_match_all('/admin\/tags\/(\d+)\/edit/', req('GET', '/admin/tags')['body'], $m)) {
    check('tag edit', req('GET', '/admin/tags/'.$m[1][0].'/edit'));
}
if (preg_match_all('/admin\/members\/(\d+)\/edit/', req('GET', '/admin/members')['body'], $m)) {
    check('member edit', req('GET', '/admin/members/'.$m[1][0].'/edit'));
}
if (preg_match_all('/admin\/form-submissions\/(\d+)/', req('GET', '/admin/form-submissions')['body'], $m)) {
    check('submission show', req('GET', '/admin/form-submissions/'.$m[1][0]));
}

echo "OK $ok FAIL ".count($fail)."\n";
foreach ($fail as $l) {
    echo "FAIL $l\n";
}
@unlink($cookie);
exit($fail ? 1 : 0);
