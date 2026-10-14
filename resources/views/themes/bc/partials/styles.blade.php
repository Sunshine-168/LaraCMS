<style>
    @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700;900&display=swap");
    :root {
        --bg: #090909;
        --ink: #f3f1ec;
        --muted: rgba(243, 241, 236, .68);
        --line: rgba(255, 255, 255, .12);
        --sans: "Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body.ig {
        margin: 0;
        background: var(--bg);
        color: var(--ink);
        font-family: var(--sans);
        font-size: 16px;
        line-height: 1.65;
    }
    body.ig a { color: inherit; text-decoration: none; }
    body.ig img { max-width: 100%; display: block; }
    .ig-wrap, .bc-wrap { width: min(1180px, calc(100% - 48px)); margin: 0 auto; }
    .ig-kicker {
        margin: 0 0 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: rgba(243, 241, 236, .55);
    }
    .ig-top {
        position: sticky;
        top: 0;
        z-index: 30;
        background: rgba(9, 9, 9, .92);
        border-bottom: 1px solid var(--line);
    }
    .ig-bar { display: flex; align-items: center; gap: 22px; min-height: 68px; }
    .ig-brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 900; letter-spacing: .04em; }
    .ig-mark {
        width: 34px; height: 34px; display: grid; place-items: center;
        border: 1px solid #fff; font-size: 12px; font-weight: 900;
    }
    .ig-nav { display: flex; flex-wrap: nowrap; align-items: center; gap: 8px 22px; margin-left: auto; }
    .ig-nav a { color: rgba(243, 241, 236, .78); font-size: 14px; }
    .ig-nav a:hover { color: #fff; }
    .ig-nav a[aria-current="page"] { color: #fff; }
    .ig-nav a.ig-btn, .ig-btn, body.ig .cms-form button, body.ig .btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-height: 40px; padding: 0 16px;
        background: #fff; color: #111; border: 0;
        font: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
    }
    .ig-btn-line { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.4); }
    .ig-nav-btn { display: none; background: transparent; color: #fff; border: 1px solid var(--line); padding: 8px 12px; font: inherit; }
    .ig-statbar { border-bottom: 1px solid var(--line); }
    .ig-stats { margin-top: 0; border-top: 0; }
    .ig-stats div { padding: 28px 18px 28px 0; }
    .ig-cat::after {
        content: "";
        position: absolute; left: 0; right: 0; bottom: 0; height: 46%;
        background: linear-gradient(transparent, rgba(0,0,0,.78));
    }
    .ig-cat span { z-index: 1; }
    .ig-cat img, .ig-row img { transition: transform .5s ease; }
    .ig-cat:hover img, .ig-row:hover img { transform: scale(1.05); }
    .ig-row div { justify-content: center; }
    .ig-market { border: 0; background: transparent; min-height: 0; padding: 8px 0 0; }
    .ig-float { display: none; }
    .ig-hero {
        min-height: calc(100vh - 124px);
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        align-items: stretch;
        background: #090909;
        padding: 0;
    }
    .ig-hero-copy { display: flex; flex-direction: column; justify-content: center; padding: 72px 7vw; }
    .ig-hero-photo { background: url("/themes/bc/stock/floor.jpg") center 40%/cover no-repeat; min-height: 520px; }
    .ig-hero h1 {
        margin: 0 0 16px;
        font-size: clamp(46px, 5.2vw, 76px);
        font-weight: 900;
        line-height: 1.08;
        letter-spacing: 0;
    }
    .ig-lead { margin: 0; max-width: 460px; color: var(--muted); font-size: 18px; }
    .ig-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }
    .ig-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        margin: 0;
        border: 0;
    }
    .ig-stats div { padding: 26px 0; }
    .ig-stats b { display: block; font-size: 40px; font-weight: 900; line-height: 1; letter-spacing: -.04em; }
    .ig-stats span { color: var(--muted); font-size: 13px; letter-spacing: .08em; }
    .ig-section { padding: 88px 0; }
    .ig-section h2 { margin: 0; font-size: clamp(32px, 4vw, 52px); font-weight: 900; line-height: 1.1; letter-spacing: -.03em; }
    .ig-split { display: grid; grid-template-columns: 1.05fr .95fr; gap: 48px; align-items: end; }
    .ig-split p { color: var(--muted); font-size: 18px; }
    .ig-frame { min-height: 460px; background: #000 center/cover no-repeat; }
    .ig-mosaic {
        display: grid;
        grid-template-columns: 1.35fr 1fr 1fr;
        grid-template-rows: 250px 250px;
        gap: 4px;
    }
    .ig-cat { position: relative; display: block; overflow: hidden; min-height: 220px; background: #000; }
    .ig-mosaic .ig-cat:first-child { grid-row: 1 / span 2; min-height: 100%; }
    .ig-cat img { width: 100%; height: 100%; object-fit: cover; min-height: 220px; }
    .ig-cat span {
        position: absolute; left: 16px; bottom: 14px;
        font-size: 22px; font-weight: 900; letter-spacing: -.02em;
        text-shadow: 0 1px 8px rgba(0,0,0,.45);
    }
    .ig-rows { display: flex; flex-direction: column; }
    .ig-row {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        min-height: 420px;
        border-top: 1px solid var(--line);
        overflow: hidden;
    }
    .ig-row:nth-child(even) { grid-template-columns: .85fr 1.15fr; }
    .ig-row:nth-child(even) img { order: 2; }
    .ig-row img { width: 100%; height: 100%; min-height: 320px; object-fit: cover; }
    .ig-row div { display: flex; flex-direction: column; justify-content: center; padding: 48px 56px; }
    .ig-row h3 { margin: 8px 0; font-size: 36px; font-weight: 900; letter-spacing: -.03em; }
    .ig-row p { margin: 0; color: var(--muted); max-width: 420px; }
    .ig-row em { margin-top: 18px; font-style: normal; font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }
    .ig-work { display: grid; grid-template-columns: 1.15fr .85fr; gap: 28px; align-items: stretch; }
    .ig-work .ig-frame { min-height: 520px; background-image: url("/themes/bc/stock/desk.jpg"); }
    .ig-mod { display: grid; grid-template-columns: 28px 1fr; gap: 14px; padding: 18px 0; border-top: 1px solid var(--line); }
    .ig-mod svg { width: 22px; height: 22px; margin-top: 4px; }
    .ig-mod h3 { margin: 0 0 4px; font-size: 18px; }
    .ig-mod p { margin: 0; color: var(--muted); }
    .ig-city {
        padding: 88px 0;
        background:
            linear-gradient(180deg, rgba(0,0,0,.35), rgba(0,0,0,.72)),
            url("/themes/bc/stock/city.jpg") center/cover no-repeat;
    }
    .ig-markets { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; margin-top: 40px; border-top: 1px solid rgba(255,255,255,.28); }
    .ig-market { display: block; min-height: 0; padding: 22px 24px 0 0; border: 0; border-right: 1px solid rgba(255,255,255,.18); background: transparent; }
    .ig-market:last-child { border-right: 0; }
    .ig-market b { display: block; font-size: 28px; font-weight: 900; }
    .ig-market span { color: var(--muted); }
    .ig-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 36px; }
    .ig-step b { display: block; font-size: 42px; font-weight: 900; letter-spacing: -.04em; color: rgba(255,255,255,.28); }
    .ig-step strong { display: block; margin: 8px 0; font-size: 20px; }
    .ig-step span { color: var(--muted); }
    .ig-news { display: grid; grid-template-columns: 1fr; }
    .ig-news a, .bc-news a {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 24px;
        align-items: baseline;
        padding: 22px 0;
        border-top: 1px solid var(--line);
        background: transparent;
        border-left: 0;
        border-right: 0;
        border-bottom: 0;
    }
    .ig-news h3, .bc-news h3 { margin: 0 0 6px; font-size: 24px; font-weight: 700; }
    .ig-news p, .bc-news p, .ig-news time, .bc-news time { margin: 0; color: var(--muted); }
    .ig-contact { display: grid; grid-template-columns: .9fr 1.1fr; min-height: 520px; }
    .ig-contact-photo { background: url("/themes/bc/stock/hero.jpg") center/cover no-repeat; }
    .ig-form { padding: 64px 56px; max-width: 560px; }
    body.ig .cms-form .cms-form-item { margin: 0 0 14px; }
    body.ig .cms-form label { display: block; margin-bottom: 6px; font-size: 13px; letter-spacing: .06em; }
    body.ig .cms-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]),
    body.ig .cms-form textarea, body.ig .cms-form select {
        width: 100%; max-width: none; padding: 12px 0;
        background: transparent; color: #fff;
        border: 0; border-bottom: 1px solid var(--line); border-radius: 0;
    }
    .ig-pagehead {
        padding: 72px 0 48px;
        background:
            linear-gradient(180deg, rgba(0,0,0,.45), rgba(0,0,0,.72)),
            url("/themes/bc/stock/neon.jpg") center/cover no-repeat;
    }
    .ig-pagehead h1, .bc-page h1 { margin: 0; font-size: clamp(36px, 5vw, 64px); font-weight: 900; letter-spacing: -.03em; line-height: 1.05; }
    .ig-pagehead .breadcrumb, .ig-pagehead .breadcrumb a, .ig-pagehead .ig-lead, .bc-lead { color: var(--muted); }
    .bc-page { padding: 36px 0 64px; }
    .bc-prose { max-width: 720px; }
    .bc-prose h2 { margin: 28px 0 8px; font-size: 28px; }
    .ig-hero-inline img { width: 100%; height: 360px; object-fit: cover; margin-bottom: 28px; }
    .ig-foot { border-top: 1px solid var(--line); padding: 48px 0 28px; color: var(--muted); font-size: 14px; }
    .ig-foot-grid { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: 24px; }
    .ig-foot a { display: block; margin: 8px 0; color: var(--muted); }
    .ig-foot h3 { margin: 0 0 8px; color: #fff; font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }
    .ig-foot .ig-brand { color: #fff; margin-bottom: 12px; }
    .ig-legal { margin-top: 28px; padding-top: 16px; border-top: 1px solid var(--line); }
    .ig-float { position: fixed; right: 18px; bottom: 18px; z-index: 40; background: #fff; color: #111; font-weight: 800; padding: 12px 16px; }
    .bc-cards, .ig-packs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
    .ig-pack { display: block; background: #111; }
    .ig-pack img { width: 100%; height: 220px; object-fit: cover; }
    .ig-pack div { padding: 16px; }
    .ig-pack h3 { margin: 0 0 6px; }
    .ig-pack p, .ig-pack em { color: var(--muted); font-style: normal; }
    .bc-pager { margin: 20px 0 40px; }
    @media (max-width: 900px) {
        .ig-split, .ig-work, .ig-contact, .ig-row, .ig-row:nth-child(even), .ig-steps, .ig-markets, .ig-stats, .ig-foot-grid, .ig-packs, .bc-cards, .ig-news a, .bc-news a {
            grid-template-columns: 1fr;
        }
        .ig-row:nth-child(even) img { order: 0; }
        .ig-mosaic { grid-template-columns: 1fr 1fr; grid-template-rows: none; }
        .ig-mosaic .ig-cat:first-child { grid-row: auto; grid-column: 1 / -1; min-height: 240px; }
        .ig-row div { padding: 28px 20px; }
        .ig-market, .ig-market:last-child { border-right: 0; border-bottom: 1px solid rgba(255,255,255,.18); padding: 18px 0; }
        .ig-hero { min-height: auto; grid-template-columns: 1fr; }
        .ig-hero-copy { padding: 40px 20px; }
        .ig-hero-photo { min-height: 280px; }
        .ig-float { display: inline-block; }
        .ig-nav-btn { display: inline-block; margin-left: auto; }
        .ig-nav { display: none; position: absolute; left: 0; right: 0; top: 68px; padding: 12px 20px 18px; background: #090909; border-bottom: 1px solid var(--line); }
        .ig-nav.is-open { display: flex; }
        .ig-form { padding: 28px 0; }
        .ig-contact-photo { min-height: 220px; }
    }
</style>
