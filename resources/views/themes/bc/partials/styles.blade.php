<style>
    @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;600&family=Noto+Serif+SC:wght@500;600&family=Source+Sans+3:wght@400;500;600&family=Source+Serif+4:wght@500;600&display=swap");
    :root {
        --bg: #07080b;
        --ink: #f4efe6;
        --muted: rgba(244, 239, 230, .62);
        --line: rgba(244, 239, 230, .12);
        --gold: #c6a56a;
        --gold-dim: rgba(198, 165, 106, .18);
        --card: #101114;
        --sans: "Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif;
        --serif: "Noto Serif SC", "Songti SC", serif;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; overflow-x: hidden; }
    section[id] { scroll-margin-top: 88px; }
    body.ig {
        margin: 0;
        max-width: 100%;
        background: var(--bg);
        color: var(--ink);
        font-family: var(--sans);
        font-size: 16px;
        line-height: 1.7;
    }
    body.ig a { color: inherit; text-decoration: none; }
    body.ig img { max-width: 100%; display: block; }
    body.ig h1, body.ig h2, .skin-name h3, .ig-pack h3 {
        font-family: var(--serif);
        font-weight: 500;
    }
    .ig-wrap, .bc-wrap { width: min(1180px, 100%); margin-inline: auto; padding-inline: 24px; }
    .ig-kicker {
        margin: 0 0 12px;
        font-size: 11px;
        font-weight: 500;
        letter-spacing: .2em;
        text-transform: uppercase;
        color: var(--gold);
    }
    .ig-top {
        position: sticky; top: 0; z-index: 30;
        background: rgba(7, 8, 11, .86);
        border-bottom: 1px solid var(--line);
        backdrop-filter: blur(16px);
    }
    .ig-bar { display: flex; align-items: center; gap: 22px; min-height: 72px; }
    .ig-brand { display: inline-flex; align-items: center; min-width: 0; gap: 10px; color: var(--ink); font-weight: 500; letter-spacing: .08em; }
    body.ig .ig-logo { height: 44px; max-height: 44px; width: auto; max-width: 168px; min-width: 0; flex: none; object-fit: contain; object-position: left center; }
    .ig-foot .ig-logo { height: 72px; }
    .ig-mark {
        width: 32px; height: 32px; display: grid; place-items: center;
        border: 1px solid var(--gold); color: var(--gold);
        font-size: 11px; letter-spacing: .04em; font-weight: 500;
    }
    .ig-nav { display: flex; align-items: center; gap: 8px 22px; margin-left: auto; }
    .ig-nav a { color: var(--muted); font-size: 13px; letter-spacing: .06em; }
    .ig-nav a:hover, .ig-nav a[aria-current="page"] { color: var(--ink); }
    .ig-btn, body.ig .cms-form button, body.ig .btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-height: 42px; padding: 0 18px;
        background: transparent; color: var(--gold);
        border: 1px solid rgba(198, 165, 106, .55);
        font: inherit; font-size: 13px; letter-spacing: .08em; cursor: pointer;
        transition: background .2s ease, color .2s ease, border-color .2s ease;
    }
    .ig-btn:hover { background: rgba(198, 165, 106, .12); color: var(--ink); }
    .ig-btn:focus-visible, .ig-tg:focus-visible, .ig-filters button:focus-visible, .ig-pack:focus-visible {
        outline: 1px solid var(--gold); outline-offset: 3px;
    }
    .ig-btn-solid, .ig-nav a.ig-btn {
        background: var(--gold); color: #16120c; border-color: var(--gold);
    }
    .ig-btn-solid:hover, .ig-nav a.ig-btn:hover { background: #d7b97e; color: #16120c; }
    .ig-nav-actions { display: inline-flex; align-items: center; gap: 10px; margin-left: 6px; padding-left: 16px; border-left: 1px solid var(--line); }
    .ig-tg {
        display: inline-flex; align-items: center; gap: 8px;
        height: 40px; padding: 0 14px;
        border: 1px solid rgba(198, 165, 106, .7);
        color: var(--gold); background: rgba(198, 165, 106, .08);
        font-size: 13px; letter-spacing: .02em; line-height: 1;
    }
    .ig-nav a.ig-tg { color: var(--gold); }
    .ig-tg svg { width: 15px; height: 15px; fill: currentColor; flex: none; }
    .ig-tg:hover, .ig-nav a.ig-tg:hover { background: var(--gold); color: #16120c; }
    .ig-foot a.ig-tg { display: inline-flex; width: auto; margin: 16px 0 0; color: var(--gold); }
    .ig-nav .lang { color: var(--muted); font-size: 12px; letter-spacing: 0; }
    html[lang^="en"] body.ig { font-family: "Source Sans 3", "Segoe UI", sans-serif; }
    html[lang^="en"] body.ig h1,
    html[lang^="en"] body.ig h2,
    html[lang^="en"] .skin-name h3,
    html[lang^="en"] .ig-pack h3,
    html[lang^="en"] .ig-hero-stats b,
    html[lang^="en"] .ig-desk-metrics b,
    html[lang^="en"] .ig-desk-top strong { font-family: "Source Serif 4", Georgia, serif; }
    html[lang^="en"] .ig-hero-stats { flex-wrap: wrap; }
    html[lang^="en"] .ig-nav a,
    html[lang^="en"] .ig-btn,
    html[lang^="en"] body.ig .cms-form button,
    html[lang^="en"] body.ig .btn { letter-spacing: .02em; }
    html[lang^="en"] .ig-kicker,
    html[lang^="en"] .ig-hero-stats span,
    html[lang^="en"] .ig-note strong,
    html[lang^="en"] .ig-filters button,
    html[lang^="en"] .ig-foot h3,
    html[lang^="en"] .skin-name span { letter-spacing: .08em; }
    .ig-nav .lang a[aria-current="page"] { color: var(--gold); }
    .ig-tg-quick { display: none; }
    .ig-nav-btn {
        display: none; margin-left: auto;
        background: transparent; color: var(--ink); border: 1px solid var(--line);
        padding: 8px 12px; font: inherit;
    }
    .ig-hero {
        display: grid;
        grid-template-columns: .82fr 1.18fr;
        gap: 64px;
        align-items: center;
        padding: 72px max(24px, calc((100% - 1180px) / 2)) 80px;
        background:
            radial-gradient(640px 420px at 78% 55%, rgba(198,165,106,.18), transparent 68%),
            linear-gradient(180deg, #10131a 0%, #07080b 100%);
    }
    .ig-hero h1 {
        margin: 0 0 18px;
        font-size: clamp(52px, 5.6vw, 84px);
        line-height: 1.02;
        letter-spacing: -.03em;
    }
    .ig-hero .ig-lead { color: #e7e0d4; font-size: 16px; max-width: 26em; }
    .ig-lead { margin: 0; max-width: 440px; color: var(--muted); font-size: 15px; }
    .ig-hero .ig-btn { min-height: 46px; padding: 0 22px; }
    .ig-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
    .ig-hero-stats { display: flex; gap: 32px; margin-top: 40px; padding-top: 22px; border-top: 1px solid var(--line); }
    .ig-hero-stats b { display: block; font-family: var(--serif); font-size: 28px; font-weight: 500; color: var(--gold); line-height: 1; }
    .ig-hero-stats span { color: var(--muted); font-size: 12px; letter-spacing: .12em; }
    .ig-note { border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .ig-note-row { display: grid; grid-template-columns: 140px 1fr; gap: 24px; align-items: center; padding: 16px 0; }
    .ig-note strong { color: var(--gold); font-weight: 500; letter-spacing: .16em; font-size: 12px; }
    .ig-note p { margin: 0; color: var(--muted); font-size: 13px; }
    .ig-gallery, .ig-section { padding: 88px 0; }
    .ig-gallery h2, .ig-section h2, .ig-consult h2 {
        margin: 0;
        font-size: clamp(32px, 3.6vw, 52px);
        line-height: 1.15;
    }
    .ig-gallery-head {
        display: grid; grid-template-columns: 1.1fr .9fr; gap: 32px;
        align-items: end; margin-bottom: 28px;
    }
    .ig-gallery-head p, .ig-textlink { color: var(--muted); }
    .ig-textlink { border-bottom: 1px solid rgba(198,165,106,.4); color: var(--gold); }
    .ig-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 22px; }
    .ig-ico { width: 22px; height: 22px; display: block; color: var(--gold); margin: 0 0 12px; flex: none; }
    .ig-filters button {
        display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid var(--line); background: transparent; color: var(--muted);
        padding: 7px 14px; font: inherit; font-size: 12px; letter-spacing: .12em; cursor: pointer;
    }
    .ig-filters .ig-ico { width: 14px; height: 14px; margin: 0; color: currentColor; }
    .ig-points .ig-ico { margin: 0; }
    .ig-filters button:hover { color: var(--ink); border-color: rgba(198, 165, 106, .45); }
    .ig-filters button.is-on, .ig-filters button.is-on:hover { color: #16120c; background: var(--gold); border-color: var(--gold); }
    .ig-skins { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; align-items: stretch; }
    .skin {
        display: flex; flex-direction: column;
        background: var(--card); border: 1px solid var(--line);
        transition: border-color .2s ease, transform .2s ease;
    }
    .skin:hover { border-color: rgba(198, 165, 106, .45); }
    .skin[hidden] { display: none; }
    .skin-shot { position: relative; background: #0c0d11; }
    .skin-chrome {
        display: flex; align-items: center; gap: 6px; height: 28px; padding: 0 12px;
        border-bottom: 1px solid rgba(255,255,255,.06); background: #08090c;
    }
    .skin-chrome i { width: 7px; height: 7px; border-radius: 50%; background: rgba(244,239,230,.28); }
    .skin-chrome i:nth-child(1) { background: #a85a4c; }
    .skin-chrome i:nth-child(2) { background: #c6a56a; }
    .skin-chrome i:nth-child(3) { background: #6d8a72; }
    .ig-hero-stage .skin-shot {
        border: 1px solid rgba(198, 165, 106, .5);
        box-shadow: 0 36px 80px rgba(0, 0, 0, .5);
        background: #090b10;
    }
    .skin-shot.is-hero .skin-stage { padding: 16px 16px 18px; }
    .skin-shot.is-hero .skin-feature { min-height: 300px; }
    .skin-shot.is-hero .skin-side div { min-height: 142px; }
    .skin-shot.is-hero .skin-banner strong,
    .skin-shot.is-hero .skin-feature strong { font-size: 28px; }
    .skin-chrome em { margin-left: 8px; font-style: normal; font-size: 11px; letter-spacing: .08em; color: rgba(244,239,230,.45); }
    .skin-stage { padding: 12px; }
    .skin-nav { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 11px; color: rgba(244,239,230,.55); white-space: nowrap; }
    .skin-shot:not(.is-hero) .skin-nav span:nth-of-type(n+4) { display: none; }
    .skin-nav strong { color: var(--gold); letter-spacing: .14em; font-weight: 500; margin-right: 4px; }
    .skin-nav span.is-on { color: #f4efe6; }
    .skin-nav em { margin-left: auto; font-style: normal; border: 1px solid rgba(198,165,106,.45); color: var(--gold); padding: 2px 8px; }
    .skin-banner, .skin-feature {
        position: relative; display: flex; flex-direction: column; justify-content: flex-end;
        min-height: 108px; padding: 12px; color: #fff; overflow: hidden;
        background:
            linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.72)),
            var(--shot) center/cover no-repeat;
    }
    .skin-shot.is-hero .skin-banner { min-height: 220px; }
    .skin-banner strong, .skin-feature strong { font-family: var(--serif); font-size: 20px; font-weight: 500; line-height: 1.2; }
    .skin-banner strong i, .skin-match-teams i { font-style: normal; opacity: .55; padding: 0 6px; font-size: .8em; }
    .skin-banner small, .skin-feature small { letter-spacing: .16em; font-size: 10px; color: var(--gold); }
    .skin-banner.is-short { min-height: 72px; }
    .skin-board { display: grid; gap: 8px; margin-top: 8px; }
    .skin-match { padding: 8px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); }
    .skin-match-top, .skin-match-line { display: flex; align-items: center; gap: 8px; }
    .skin-match-top { font-size: 10px; letter-spacing: .06em; color: rgba(244,239,230,.48); margin-bottom: 6px; }
    .skin-match-top b { color: #e07a72; font-weight: 500; }
    .skin-match-top span:last-child { margin-left: auto; }
    .skin-match-line { font-size: 13px; }
    .skin-match-line span:last-child { margin-left: auto; }
    .skin-match-line strong { font-family: var(--serif); font-weight: 500; color: var(--gold); font-size: 14px; }
    .skin-odds { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px; }
    .skin-odds em {
        display: flex; flex-direction: column; align-items: center; gap: 1px;
        font-style: normal; font-size: 13px; color: #f4efe6;
        padding: 5px 0 6px;
        border: 1px solid rgba(198,165,106,.32); background: rgba(198,165,106,.08);
    }
    .skin-odds small { font-size: 9px; letter-spacing: .12em; color: rgba(244,239,230,.45); }
    .skin-lobby { display: grid; grid-template-columns: 1.35fr .85fr; gap: 8px; }
    .skin-feature span, .skin-side span { font-size: 11px; color: rgba(255,255,255,.75); }
    .skin-side { display: grid; gap: 8px; }
    .skin-side div {
        display: flex; flex-direction: column; justify-content: flex-end; gap: 4px; padding: 10px;
        background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); min-height: 72px;
    }
    .skin-side b { display: flex; align-items: center; gap: 6px; font-weight: 500; font-size: 13px; }
    .skin-live {
        display: inline-block; width: 6px; height: 6px; margin-right: 6px; border-radius: 50%;
        background: #e07a72; box-shadow: 0 0 0 3px rgba(224, 122, 114, .18); vertical-align: 1px;
    }
    .skin-side .skin-live { margin-right: 0; }
    .skin-road { display: flex; gap: 4px; margin-top: 8px; }
    .skin-road i { width: 7px; height: 7px; border-radius: 50%; }
    .skin-road .p { background: #6ea2e0; }
    .skin-road .bk { background: #e07a72; }
    .skin-road .t { background: transparent; box-shadow: inset 0 0 0 1px #7dbe8d; }
    .skin-tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px; }
    .skin-tiles i {
        display: flex; flex-direction: column; justify-content: flex-end; gap: 2px;
        min-height: 72px; padding: 8px;
        background-image: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.78)), var(--shot);
        background-size: cover, cover;
        background-repeat: no-repeat;
        font-style: normal;
    }
    .skin-tiles i:nth-child(1) { background-position: center, 0% 20%; }
    .skin-tiles i:nth-child(2) { background-position: center, 70% 10%; }
    .skin-tiles i:nth-child(3) { background-position: center, 30% 80%; }
    .skin-tiles i:nth-child(4) { background-position: center, 90% 60%; }
    .skin-tiles i:nth-child(5) { background-position: center, 15% 45%; }
    .skin-tiles i:nth-child(6) { background-position: center, 55% 90%; }
    .skin-tiles b { font-weight: 500; font-size: 12px; }
    .skin-tiles small { font-size: 10px; color: rgba(255,255,255,.7); }
    .skin-shot.is-hero .skin-tiles i { min-height: 96px; }
    .tone-arctic .skin-odds em { border-color: rgba(213,228,245,.35); background: rgba(120,160,210,.12); }
    .tone-crimson .skin-feature { box-shadow: inset 0 0 0 1px rgba(180,60,70,.28); }
    .skin-body { margin-top: auto; padding: 16px 16px 18px; border-top: 1px solid var(--line); }
    .skin-name { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .skin-name h3 { margin: 0; font-size: 22px; }
    .skin-name span { color: var(--gold); font-size: 11px; letter-spacing: .18em; }
    .skin-body p { margin: 8px 0 14px; color: var(--muted); font-size: 13px; min-height: 2.8em; }
    .skin { cursor: pointer; }
    .skin:focus-visible { outline: 1px solid var(--gold); outline-offset: 3px; }
    .skin-ends { display: flex; gap: 8px; }
    .skin-ends button {
        padding: 0 0 2px; border: 0; border-bottom: 1px solid var(--line);
        background: transparent; color: var(--muted);
        font: inherit; font-size: 11px; letter-spacing: .14em; cursor: pointer;
    }
    .skin-ends button:hover { color: var(--gold); border-bottom-color: var(--gold); }
    .skin-slip { display: grid; grid-template-columns: 72px 1fr 78px; gap: 8px; align-items: stretch; }
    .skin-leagues { display: flex; flex-direction: column; gap: 4px; }
    .skin-leagues span { padding: 6px 4px; font-size: 11px; color: rgba(244,239,230,.55); border-left: 2px solid transparent; }
    .skin-leagues span.is-on { color: var(--gold); border-left-color: var(--gold); }
    .skin-betslip { display: flex; flex-direction: column; gap: 4px; padding: 8px; border: 1px solid rgba(198,165,106,.35); font-size: 11px; }
    .skin-betslip b { color: var(--gold); font-weight: 500; letter-spacing: .12em; }
    .skin-betslip em { font-style: normal; font-size: 16px; color: #f4efe6; }
    .skin-betslip i { font-style: normal; padding: 4px; border: 1px solid rgba(255,255,255,.12); color: rgba(244,239,230,.7); }
    .skin-betslip strong { margin-top: auto; text-align: center; padding: 6px 0; background: var(--gold); color: #16120c; font-weight: 500; }
    .skin-tables { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .skin-tables div {
        min-height: 78px; padding: 8px; display: flex; flex-direction: column; justify-content: flex-end;
        background-image: linear-gradient(180deg, rgba(0,0,0,.1), rgba(0,0,0,.72)), var(--shot);
        background-size: cover; color: #fff;
    }
    .skin-tables div:nth-child(2) { background-position: center, 80% 20%; }
    .skin-tables div:nth-child(3) { background-position: center, 20% 80%; }
    .skin-tables div:nth-child(4) { background-position: center, 70% 70%; }
    .skin-tables small { font-size: 10px; letter-spacing: .12em; color: var(--gold); }
    .skin-tables b { font-weight: 500; font-size: 13px; }
    .skin-tables span, .skin-hall span, .skin-rooms span { font-size: 11px; color: rgba(255,255,255,.75); }
    .skin-hall { display: grid; grid-template-columns: 1.3fr 1fr; grid-template-rows: 1fr 1fr; gap: 6px; min-height: 168px; }
    .skin-hall div:first-child { grid-row: 1 / span 2; }
    .skin-hall div {
        display: flex; flex-direction: column; justify-content: flex-end; padding: 10px; color: #fff;
        background-image: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.72)), var(--shot);
        background-size: cover; background-position: center;
    }
    .skin-hall strong { font-family: var(--serif); font-size: 18px; font-weight: 500; }
    .skin-jackpot { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; padding: 8px 10px; border: 1px solid rgba(198,165,106,.35); }
    .skin-jackpot small { letter-spacing: .16em; font-size: 10px; color: var(--gold); }
    .skin-jackpot strong { font-family: var(--serif); font-size: 22px; font-weight: 500; color: var(--gold); }
    .skin-rail { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .skin-rail i {
        min-height: 86px; display: flex; align-items: flex-end; padding: 8px; font-style: normal;
        background-image: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.75)), var(--shot);
        background-size: cover;
    }
    .skin-rail i:nth-child(2) { background-position: center, 70% 20%; }
    .skin-rail i:nth-child(3) { background-position: center, 20% 70%; }
    .skin-rail i:nth-child(4) { background-position: center, 80% 80%; }
    .skin-rail b { font-weight: 500; font-size: 12px; }
    .skin-rooms { display: grid; gap: 6px; }
    .skin-rooms div { display: grid; grid-template-columns: 1fr auto auto; gap: 8px; align-items: center; padding: 8px; border: 1px solid rgba(255,255,255,.08); }
    .skin-rooms b { font-weight: 500; font-size: 13px; }
    .skin-rooms span { color: rgba(244,239,230,.55); }
    .skin-rooms em { font-style: normal; font-size: 11px; color: var(--gold); border: 1px solid rgba(198,165,106,.4); padding: 3px 8px; }
    .skin-draw { display: grid; gap: 8px; }
    .skin-clock { display: flex; justify-content: space-between; align-items: baseline; }
    .skin-clock small { letter-spacing: .14em; font-size: 10px; color: var(--gold); }
    .skin-clock strong { font-family: var(--serif); font-size: 28px; font-weight: 500; }
    .skin-balls { display: flex; gap: 6px; }
    .skin-balls i { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; border: 1px solid var(--gold); color: var(--gold); font-style: normal; font-size: 12px; }
    .skin-plays { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .skin-plays span { text-align: center; padding: 8px 0; border: 1px solid rgba(198,165,106,.35); font-size: 13px; }
    .ig-preview {
        width: min(980px, calc(100% - 28px)); max-height: min(860px, calc(100% - 28px));
        margin: auto; padding: 0; border: 1px solid rgba(198,165,106,.35);
        background: #0c0d11; color: var(--ink);
    }
    .ig-preview::backdrop { background: rgba(0,0,0,.72); }
    .ig-preview-bar { display: flex; align-items: center; gap: 16px; padding: 14px 16px; border-bottom: 1px solid var(--line); }
    .ig-preview-title { font-family: var(--serif); font-size: 20px; font-weight: 500; }
    .ig-preview-tabs { display: flex; gap: 8px; margin-left: auto; }
    .ig-preview-tabs button, .ig-preview-x {
        background: transparent; color: var(--muted); border: 1px solid var(--line);
        font: inherit; font-size: 12px; letter-spacing: .12em; padding: 6px 12px; cursor: pointer;
    }
    .ig-preview-tabs button.is-on { color: #16120c; background: var(--gold); border-color: var(--gold); }
    .ig-preview-body { padding: 16px; overflow: auto; }
    .ig-preview [data-pane-panel="front"] .skin-shot { border: 1px solid var(--line); }
    .ig-desk-lg { min-height: 420px; }
    .ig-agent { border: 1px solid var(--line); padding: 16px; background: #090a0d; }
    .ig-agent-link { display: flex; justify-content: space-between; gap: 12px; margin: 12px 0; padding: 10px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); font-size: 13px; }
    .ig-agent-link em { font-style: normal; color: var(--gold); }
    .ig-mosaic { display: grid; grid-template-columns: 1.4fr 1fr 1fr; grid-template-rows: 190px 190px; gap: 8px; }
    .ig-cat { position: relative; overflow: hidden; min-height: 180px; background: #111; }
    .ig-mosaic .ig-cat:first-child { grid-row: 1 / span 2; }
    .ig-cat img { width: 100%; height: 100%; object-fit: cover; min-height: 180px; filter: saturate(.85); transition: transform .45s ease; }
    .ig-cat:hover img { transform: scale(1.04); }
    .ig-cat::after { content: ""; position: absolute; inset: auto 0 0; height: 46%; background: linear-gradient(transparent, rgba(0,0,0,.75)); }
    .ig-cat span { position: absolute; left: 14px; bottom: 12px; z-index: 1; font-family: var(--serif); font-size: 22px; }
    .ig-section-ink { border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); background: #0c0d11; }
    .ig-console { display: grid; grid-template-columns: .88fr 1.12fr; gap: 40px; align-items: center; }
    .ig-points { list-style: none; margin: 26px 0 0; padding: 0; }
    .ig-points li { display: grid; grid-template-columns: 22px 88px 1fr; gap: 12px; align-items: center; padding: 14px 0; border-top: 1px solid var(--line); }
    .ig-points b { color: var(--gold); font-weight: 500; }
    .ig-points span { color: var(--muted); }
    .ig-desk { display: grid; grid-template-columns: 64px 1fr; min-height: 340px; border: 1px solid var(--line); background: #090a0d; }
    .ig-desk-side { padding: 16px 12px; display: flex; flex-direction: column; gap: 12px; border-right: 1px solid var(--line); font-size: 12px; color: var(--muted); }
    .ig-desk-side b { color: var(--gold); letter-spacing: .14em; font-weight: 500; }
    .ig-desk-side span.is-on { color: var(--ink); }
    .ig-desk-main { padding: 16px; }
    .ig-desk-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
    .ig-desk-top strong { font-family: var(--serif); font-weight: 500; font-size: 18px; }
    .ig-desk-top em { font-style: normal; font-size: 12px; color: var(--gold); }
    .ig-sample { color: var(--muted) !important; letter-spacing: .14em; }
    .ig-fit, .ig-asks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .ig-asks { grid-template-columns: 1fr 1fr; }
    .ig-fit a, .ig-asks div, .ig-regions a { display: block; border: 1px solid var(--line); padding: 18px 18px 20px; }
    .ig-fit a:hover, .ig-regions a:hover { border-color: var(--gold); }
    .ig-fit h3, .ig-asks h3, .ig-modgrid b, .ig-regions b { margin: 0 0 8px; font-size: 18px; font-weight: 500; }
    .ig-fit p, .ig-asks p, .ig-modgrid span, .ig-regions span { margin: 0; color: var(--muted); }
    .ig-regions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .ig-regions b { display: block; }
    .ig-mods { margin-top: 48px; }
    .ig-modgrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .ig-modgrid div { border-top: 1px solid var(--line); padding: 16px 16px 16px 0; }
    .ig-modgrid b { display: block; color: var(--gold); font-weight: 500; }
    .ig-check { list-style: none; margin: 12px 0 8px; padding: 0; }
    .ig-check li { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; border-top: 1px solid var(--line); font-size: 13px; }
    .ig-check i { font-style: normal; color: var(--gold); text-align: right; }
    .ig-check li.is-off, .ig-check li.is-off i { color: var(--muted); }
    .ig-desk-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .ig-desk-metrics div { padding: 12px; background: var(--gold-dim); border: 1px solid rgba(198,165,106,.25); }
    .ig-desk-metrics small { display: block; color: var(--muted); font-size: 11px; letter-spacing: .08em; }
    .ig-desk-metrics b { font-family: var(--serif); font-size: 22px; font-weight: 500; }
    .ig-desk-table { margin-top: 10px; display: grid; }
    .ig-desk-table div { display: grid; grid-template-columns: 52px 1fr 48px auto; gap: 8px; align-items: center; padding: 8px 0; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); }
    .ig-desk-table b { color: var(--ink); font-weight: 500; }
    .ig-packs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; align-items: stretch; }
    .ig-pack { display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--line); transition: border-color .2s ease; }
    .ig-pack:hover { border-color: rgba(198, 165, 106, .55); }
    .ig-pack img { width: 100%; height: 160px; object-fit: cover; filter: saturate(.75); }
    .ig-pack div { display: flex; flex-direction: column; flex: 1; padding: 14px 16px 18px; }
    .ig-pack h3 { margin: 0 0 6px; font-size: 22px; }
    .ig-pack p { color: var(--muted); }
    .ig-pack em { margin-top: auto; padding-top: 14px; color: var(--gold); font-style: normal; font-size: 13px; letter-spacing: .06em; }
    .ig-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 28px; }
    .ig-step { border: 1px solid var(--line); padding: 18px; }
    .ig-step b { font-family: var(--serif); font-size: 28px; color: var(--gold); }
    .ig-step strong { display: block; margin: 10px 0 6px; font-weight: 500; }
    .ig-step span { color: var(--muted); font-size: 14px; }
    .ig-news a, .bc-news a { display: grid; grid-template-columns: 140px 1fr; gap: 18px; padding: 18px 0; border-top: 1px solid var(--line); transition: color .2s ease; }
    .ig-news a:hover h3, .bc-news a:hover h3 { color: var(--gold); }
    .ig-news h3, .bc-news h3 { margin: 0 0 4px; font-family: var(--serif); font-size: 22px; font-weight: 500; }
    .ig-news p, .bc-news p, .ig-news time, .bc-news time { margin: 0; color: var(--muted); }
    .ig-consult { padding: 72px 0 88px; border-top: 1px solid var(--line); }
    .ig-consult-grid, .ig-contact { display: grid; grid-template-columns: .8fr 1.2fr; gap: 48px; align-items: start; }
    .ig-consult-copy .ig-lead { margin: 14px 0 22px; color: #e4ddd0; max-width: 22em; }
    .ig-consult-copy .ig-tg { margin-bottom: 18px; }
    .ig-consult-copy .ig-textlink { color: #e4ddd0; border-bottom-color: rgba(228, 221, 208, .45); }
    .ig-form { border: 1px solid rgba(198, 165, 106, .28); padding: 28px; background: #101114; }
    body.ig .cms-form .cms-form-item { margin: 0 0 16px; }
    body.ig .cms-form label { display: block; margin-bottom: 8px; font-size: 13px; letter-spacing: .04em; color: #f0ebe3; }
    body.ig .cms-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]),
    body.ig .cms-form textarea, body.ig .cms-form select {
        width: 100%; max-width: none; padding: 12px 14px;
        background: #1a1c22; color: #f7f3ea;
        border: 1px solid rgba(198, 165, 106, .4); border-radius: 0;
    }
    body.ig .cms-form select {
        color-scheme: dark;
        background-color: #1a1c22;
        color: #f7f3ea;
        accent-color: #c6a56a;
    }
    body.ig .ig-form .cms-form button {
        margin-top: 6px; min-width: 128px;
        background: var(--gold); color: #16120c; border-color: var(--gold);
    }
    body.ig .ig-form .cms-form button:hover { background: #d7b97e; color: #16120c; }
    body.ig .cms-form select option {
        background-color: #1a1c22;
        color: #f7f3ea;
    }
    body.ig .cms-form select option:checked {
        background-color: #c6a56a;
        color: #16120c;
    }
    body.ig .cms-form input::placeholder,
    body.ig .cms-form textarea::placeholder { color: #d5cec2; opacity: 1; }
    body.ig .cms-form input:focus, body.ig .cms-form textarea:focus, body.ig .cms-form select:focus {
        outline: none; border-color: #c6a56a; border-bottom-color: #c6a56a;
    }
    .bc-pager, .bc-pager .muted, .bc-pager a { color: var(--muted); }
    .bc-pager a:hover { color: var(--gold); }
    .ig-pagehead { padding: 64px 0 40px; border-bottom: 1px solid var(--line); }
    .ig-pagehead h1 { margin: 0; font-size: clamp(36px, 4vw, 60px); line-height: 1.08; }
    .ig-pagehead .breadcrumb, .ig-pagehead .breadcrumb a, .ig-pagehead .ig-lead, .bc-lead { color: var(--muted); }
    .bc-page { padding: 32px 0 80px; }
    .bc-prose { max-width: 720px; }
    .bc-prose:has(table) { max-width: 980px; }
    .bc-prose p { margin: 0 0 14px; }
    .bc-prose ul { margin: 0 0 16px; padding-left: 1.15em; }
    .bc-prose li { margin: 8px 0; }
    .bc-prose a { color: var(--gold); border-bottom: 1px solid rgba(198, 165, 106, .4); }
    .bc-prose h2 { margin: 28px 0 8px; padding-top: 16px; border-top: 1px solid var(--line); font-size: 28px; font-weight: 500; }
    .bc-prose h2:first-child { border-top: 0; padding-top: 0; }
    body.ig .cms-search-form input,
    body.ig .cms-search-form select {
        background: #1a1c22; color: #f7f3ea;
        border: 1px solid rgba(198, 165, 106, .4); min-height: 40px;
    }
    body.ig .cms-search-form button {
        background: var(--gold); color: #16120c; border: 0; min-height: 40px; padding: 0 16px;
    }
    .ig-scope { max-width: 420px; margin: 0 0 22px; }
    .ig-date { margin: 0 0 18px; color: var(--muted); font-size: 13px; letter-spacing: .08em; }
    .ig-more { margin-top: 28px; padding-top: 8px; }
    .ig-more h2 { margin: 0 0 8px; font-size: 32px; font-weight: 500; }
    .ig-more-act { margin: 22px 0 0; }
    .bc-prose table.ig-sheet { width: 100%; border-collapse: collapse; margin: 8px 0 28px; font-size: 14px; }
    .bc-prose table.ig-sheet th, .bc-prose table.ig-sheet td { text-align: left; vertical-align: top; padding: 14px 16px 14px 0; border-bottom: 1px solid var(--line); }
    .bc-prose table.ig-sheet th { color: var(--gold); font-weight: 500; font-size: 12px; letter-spacing: .12em; }
    .ig-hero-inline .ig-mosaic { margin-bottom: 28px; }
    .ig-foot { border-top: 1px solid var(--line); padding: 56px 0 28px; color: var(--muted); font-size: 13px; }
    .ig-foot-grid { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(8em, .9fr) minmax(0, 1.35fr) minmax(0, .75fr); gap: 36px 32px; align-items: start; }
    .ig-foot-packs { position: relative; z-index: 1; }
    .ig-foot-packs a { color: #f4efe6; }
    .ig-foot-site { display: grid; grid-template-columns: 1fr 1fr; column-gap: 28px; align-items: start; }
    .ig-foot-brand p { max-width: 28em; margin: 12px 0 0; line-height: 1.7; }
    .ig-foot-col a { display: block; margin: 0; padding: 7px 0; color: rgba(244, 239, 230, .72); }
    .ig-foot-col a:hover { color: var(--gold); }
    .ig-foot h3 { margin: 0 0 8px; color: var(--gold); font-size: 12px; letter-spacing: .16em; font-weight: 500; }
    .ig-foot .ig-brand { display: inline-flex; width: auto; margin: 0; }
    .ig-foot .ig-logo { height: 64px; }
    .ig-legal { margin-top: 36px; padding-top: 16px; border-top: 1px solid var(--line); font-size: 12px; }
    .ig-legal a { color: inherit; }
    .ig-float {
        display: inline-flex; align-items: center; gap: 8px;
        position: fixed; right: 16px; bottom: 16px; z-index: 40;
        background: var(--gold); color: #16120c; letter-spacing: .02em;
        padding: 12px 16px; font-size: 14px;
    }
    .ig-float svg { width: 18px; height: 18px; fill: currentColor; flex: none; }
    .bc-pager { margin: 20px 0 8px; }
    @media (max-width: 980px) {
        .ig-wrap, .bc-wrap { padding-inline: 16px; }
        .ig-wrap, .bc-wrap, .ig-top, .ig-bar, .ig-hero, .ig-hero-copy, .ig-hero-stage, .ig-gallery-head, .ig-filters, .ig-fit, .skin, .skin-shot, .skin-stage, .ig-desk, .ig-pack, .ig-cat, main, section {
            min-width: 0; max-width: 100%;
        }
        body.ig .ig-logo { height: 34px; max-height: 34px; max-width: 128px; }
        .ig-foot .ig-logo { height: 48px; max-height: 48px; }
        .ig-bar { min-height: 56px; gap: 12px; }
        .ig-bar .ig-brand { flex: none; }
        .ig-bar .ig-logo { width: auto; height: 34px; }
        .ig-nav-btn { display: inline-flex; align-items: center; min-height: 40px; margin-left: auto; padding: 0 14px; flex: none; }
        .ig-foot a.ig-tg, .ig-consult-copy .ig-tg { display: none; }
        .ig-float {
            opacity: 0; pointer-events: none; transform: translateY(8px);
            width: 52px; height: 52px; padding: 0; border-radius: 50%;
            justify-content: center; gap: 0;
            transition: opacity .2s ease, transform .2s ease;
        }
        .ig-float span {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
        .ig-float svg { width: 22px; height: 22px; }
        .ig-float.is-on { opacity: 1; pointer-events: auto; transform: none; }
        .ig-foot { padding-bottom: 84px; }
        .ig-hero, .ig-gallery-head, .ig-console, .ig-consult-grid, .ig-contact, .ig-note-row, .ig-steps, .ig-packs, .ig-foot-grid, .ig-news a, .bc-news a, .ig-skins, .ig-fit, .ig-asks, .ig-modgrid, .ig-regions {
            grid-template-columns: minmax(0, 1fr);
        }
        .ig-foot-site { column-gap: 20px; }
        .ig-hero { min-height: 0; padding: 28px 16px 40px; gap: 28px; }
        .ig-hero h1 { font-size: 40px; letter-spacing: 0; }
        .ig-gallery, .ig-section { padding: 48px 0; }
        .ig-gallery h2, .ig-section h2, .ig-consult h2 { font-size: 32px; }
        .ig-kicker { letter-spacing: .12em; }
        .ig-actions .ig-btn { flex: 1 1 140px; }
        .ig-hero-stats { flex-wrap: wrap; gap: 18px 28px; }
        .ig-note-row { gap: 8px; padding: 14px 0; }
        .ig-filters { flex-wrap: wrap; max-width: 100%; }
        .ig-filters button { letter-spacing: .06em; }
        .ig-mosaic { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); grid-template-rows: none; }
        .ig-mosaic .ig-cat:first-child { grid-column: 1 / -1; grid-row: auto; min-height: 200px; }
        .ig-nav {
            display: none; position: absolute; left: 0; right: 0; top: 56px;
            padding: 8px 16px 16px; background: #07080b; border-bottom: 1px solid var(--line);
            flex-direction: column; align-items: stretch; gap: 0;
        }
        .ig-nav.is-open { display: flex; }
        .ig-nav a { padding: 12px 0; font-size: 16px; }
        .ig-nav-actions { margin-left: 0; padding: 8px 0 0; border-left: 0; flex-direction: column; align-items: stretch; }
        .ig-nav-actions .ig-btn, .ig-nav-actions .ig-tg { width: 100%; justify-content: center; }
        .ig-points li { grid-template-columns: 28px minmax(0, 1fr); }
        .ig-points .ig-ico { grid-row: 1 / span 2; align-self: center; }
        .ig-points b, .ig-points span { grid-column: 2; }
        .ig-step {
            display: grid; grid-template-columns: 28px minmax(0, 1fr); column-gap: 14px; row-gap: 0;
            padding: 16px 0; border: 0; border-top: 1px solid var(--line);
        }
        .ig-step .ig-ico { grid-row: 1 / span 3; align-self: center; margin: 0; width: 24px; height: 24px; }
        .ig-step b, .ig-step strong, .ig-step span { grid-column: 2; }
        .ig-step b { font-size: 22px; }
        .skin-nav { min-width: 0; max-width: 100%; overflow: hidden; flex-wrap: wrap; white-space: normal; }
        .skin-plays { grid-template-columns: repeat(2, 1fr); }
        .ig-float { display: inline-flex; }
        .skin-slip { grid-template-columns: 56px minmax(0, 1fr) 64px; }
        .ig-preview { width: auto; max-width: calc(100% - 20px); max-height: calc(100% - 20px); }
        .ig-preview-bar { flex-wrap: wrap; }
        .ig-preview-tabs { margin-left: 0; width: 100%; }
        .skin-body p { min-height: 0; }
        .ig-desk { min-height: 0; }
        .ig-desk-table div { grid-template-columns: 48px minmax(0, 1fr) auto; }
        .ig-desk-table div span:nth-child(3) { display: none; }
        .bc-prose:has(table) { max-width: none; }
        .bc-prose table.ig-sheet { display: block; overflow-x: auto; }
        .ig-form { padding: 18px; }
        .ig-consult { padding: 40px 0 72px; }
    }
    @media (max-width: 640px) {
        .ig-mosaic { grid-template-columns: minmax(0, 1fr); }
    }
    @media (min-width: 981px) {
        .ig-float, .ig-foot a.ig-tg, .ig-consult-copy .ig-tg { display: none; }
    }
</style>
