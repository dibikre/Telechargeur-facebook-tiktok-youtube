// ==UserScript==
// @name         WhoresHub Segment Compiler
// @namespace    http://tampermonkey.net/
// @version      1.1
// @description  Timeline + sélection de segments + commande FFmpeg pour whoreshub.com
// @match        https://www.whoreshub.com/videos/*
// @grant        GM_setClipboard
// @grant        GM_xmlhttpRequest
// @connect      wh.cdntrex.com
// @connect      localhost
// ==/UserScript==

(function () {
    'use strict';

    // ── Seulement sur les pages vidéo (/videos/{id}/{slug}/) ──────────────────
    if (!/^\/videos\/\d+\//.test(window.location.pathname)) return;

    // ── Config ────────────────────────────────────────────────────────────────
    const PROXY_BASE = 'http://localhost:2002/wr/proxy.php';
    const REFERER    = 'https://www.whoreshub.com/';

    // Titre exact depuis <title> — ex: "Kay Lovely vs Jaxslayher 1080p - WhoresHub - ..."
    // → on garde uniquement la première partie avant " - "
    function getVideoTitle() {
        const h1 = document.querySelector('.video-title h1.title, .video-title .title');
        if (h1) return h1.textContent.trim();
        const t = document.title || '';
        return t.split(' - ')[0].trim() || 'compilation';
    }

    // Nettoie le titre pour un nom de fichier Windows-safe
    function safeFilename(title) {
        return title.replace(/[\\/:*?"<>|]/g, '_').trim() || 'compilation';
    }

    // Construit l'URL proxy (les cookies document.cookie ne contiennent PAS les HttpOnly
    // mais le Referer seul suffit la plupart du temps ; le v-acctoken est déjà dans l'URL)
    function proxyUrl(videoUrl) {
        const params = new URLSearchParams({
            url : videoUrl,
            ref : REFERER,
        });
        const cookies = document.cookie;
        if (cookies) params.set('cookies', cookies);
        return PROXY_BASE + '?' + params.toString();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getFlashvar(key) {
        const m = document.documentElement.innerHTML.match(
            new RegExp(`['"]?${key}['"]?\\s*:\\s*['"]([^'"]{2,}['"]?)`)
        );
        if (!m) return null;
        return m[1].replace(/['"]$/, '').replace(/\\'/g, "'");
    }

    function parseDuration(str) {
        if (!str) return 0;
        const parts = str.trim().split(':').map(Number);
        if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
        if (parts.length === 2) return parts[0] * 60 + parts[1];
        return parseInt(str) || 0;
    }

    function fmtDisplay(sec) {
        sec = Math.max(0, Math.floor(sec));
        const h = Math.floor(sec / 3600);
        const m = Math.floor((sec % 3600) / 60);
        const s = sec % 60;
        if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        return `${m}:${String(s).padStart(2, '0')}`;
    }

    function fmtFFmpeg(sec) {
        sec = Math.max(0, sec);
        const h = Math.floor(sec / 3600);
        const m = Math.floor((sec % 3600) / 60);
        const s = (sec % 60).toFixed(3);
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${s.padStart(6, '0')}`;
    }

    // ── Extraction des données de la page ─────────────────────────────────────

    // Video ID
    const videoIdEl = document.querySelector('[data-video-id]');
    const videoId   = videoIdEl ? videoIdEl.dataset.videoId
                    : (getFlashvar('video_id') || '');

    // Durée depuis le DOM : <li class="wrap"><svg icon-duration...><div class="value">49:08</div>
    let totalDuration = 0;
    document.querySelectorAll('.list-info .wrap').forEach(item => {
        const use = item.querySelector('use');
        const href = use ? (use.getAttribute('href') || use.getAttribute('xlink:href') || '') : '';
        if (href.includes('icon-duration')) {
            const val = item.querySelector('.value');
            if (val) totalDuration = parseDuration(val.textContent.trim());
        }
    });
    // Fallback depuis flashvars si le DOM ne l'a pas donné
    if (!totalDuration) {
        const fvDur = getFlashvar('video_duration');
        if (fvDur) totalDuration = parseDuration(fvDur);
    }

    // Qualités vidéo (video_url, video_alt_url, video_alt_url2, video_alt_url3)
    const qualityFields = [
        { key: 'video_url',      textKey: 'video_url_text'      },
        { key: 'video_alt_url',  textKey: 'video_alt_url_text'  },
        { key: 'video_alt_url2', textKey: 'video_alt_url2_text' },
        { key: 'video_alt_url3', textKey: 'video_alt_url3_text' },
    ];
    const qualities = [];
    qualityFields.forEach(({ key, textKey }) => {
        const url = getFlashvar(key);
        if (url && /^(https?:)?\/\//.test(url)) {
            qualities.push({ url, label: getFlashvar(textKey) || key });
        }
    });

    // Timeline CDN (même pattern que porntrex mais sur wh.cdntrex.com)
    const group           = String(Math.floor(parseInt(videoId || '0') / 1000) * 1000);
    const timelineBase    = `https://wh.cdntrex.com/contents/videos_screenshots/${group}/${videoId}/timelines/timeline_mp4/200x116/`;
    let   timelineCount   = parseInt(getFlashvar('timeline_screens_count')    || '0');
    const timelineInterval= parseInt(getFlashvar('timeline_screens_interval') || '10');
    // URL template depuis flashvars (ex: //wh.cdntrex.com/.../timelines/.../{time}.jpg)
    let   timelineUrlTpl  = getFlashvar('timeline_screens_url') || '';
    if (timelineUrlTpl.startsWith('//')) timelineUrlTpl = 'https:' + timelineUrlTpl;

    function thumbUrl(n) {
        if (timelineUrlTpl) return timelineUrlTpl.replace('{time}', String(n));
        return timelineBase + n + '.jpg';
    }

    // ── CSS ───────────────────────────────────────────────────────────────────
    const css = document.createElement('style');
    css.textContent = `
        #wr-compiler {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f0f13;
            border: 1px solid #2a2a35;
            border-radius: 10px;
            padding: 16px;
            margin: 0 0 20px 0;
            color: #e0e0e0;
        }
        #wr-compiler h2 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #wr-compiler h2 span.badge {
            background: #e8431a;
            color: #fff;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 99px;
            font-weight: 700;
            letter-spacing: .5px;
        }
        /* Quality row */
        #wr-quality-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        #wr-quality-row label {
            font-size: 12px;
            color: #888;
            white-space: nowrap;
        }
        #wr-quality-select {
            flex: 1;
            background: #1a1a24;
            color: #fff;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            cursor: pointer;
        }
        /* Timeline bar */
        #wr-bar-wrap {
            position: relative;
            margin-bottom: 10px;
        }
        #wr-bar {
            width: 100%;
            height: 44px;
            background: #0a0a10;
            border: 1px solid #2a2a35;
            border-radius: 8px;
            position: relative;
            cursor: crosshair;
            overflow: hidden;
            user-select: none;
        }
        #wr-bar-hover {
            position: absolute;
            top: -30px;
            background: rgba(10,10,20,.92);
            color: #fff;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-family: monospace;
            pointer-events: none;
            display: none;
            white-space: nowrap;
            z-index: 20;
            transform: translateX(-50%);
            border: 1px solid #444;
        }
        .wr-seg-box {
            position: absolute; top: 0; height: 100%;
            background: rgba(232,67,26,.35);
            border-left: 2px solid #e8431a;
            border-right: 2px solid #e8431a;
            pointer-events: none;
        }
        .wr-pending-line {
            position: absolute; top: 0; height: 100%;
            border-left: 2px solid #2ecc71;
            pointer-events: none;
        }
        /* Legend under bar */
        #wr-bar-legend {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-family: monospace;
            color: #555;
            margin-top: 3px;
            padding: 0 2px;
        }
        /* Controls */
        #wr-controls {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .wr-btn {
            background: #1e1e2a;
            border: 1px solid #333;
            color: #ccc;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background .15s;
        }
        .wr-btn:hover { background: #2a2a3a; color: #fff; }
        .wr-btn.start { border-color: #2ecc71; color: #2ecc71; }
        .wr-btn.start:hover { background: rgba(46,204,113,.15); }
        .wr-btn.end { border-color: #e8431a; color: #e8431a; }
        .wr-btn.end:hover { background: rgba(232,67,26,.15); }
        .wr-btn.primary {
            background: #e8431a;
            border-color: #c23610;
            color: #fff;
            font-weight: 600;
        }
        .wr-btn.primary:hover { background: #c23610; }
        .wr-btn.copy {
            background: #1a3a1e;
            border-color: #2ecc71;
            color: #2ecc71;
            font-weight: 600;
        }
        .wr-btn.copy:hover { background: #1f4a24; }
        #wr-pending-info {
            font-size: 12px;
            color: #2ecc71;
            font-family: monospace;
        }
        /* Segments list */
        #wr-segments {
            margin-bottom: 10px;
            max-height: 130px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .wr-seg-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #181820;
            border: 1px solid #2a2a35;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 12px;
            font-family: monospace;
        }
        .wr-seg-item span { color: #bbb; }
        .wr-seg-dur { color: #888; font-size: 11px; margin-left: 8px; }
        .wr-seg-del {
            cursor: pointer;
            color: #e74c3c;
            font-size: 14px;
            line-height: 1;
            padding: 0 4px;
            flex-shrink: 0;
        }
        .wr-seg-del:hover { color: #ff6b6b; }
        /* ffmpeg output */
        #wr-ffmpeg-wrap {
            display: none;
            margin-top: 10px;
        }
        #wr-ffmpeg-out {
            background: #050508;
            border: 1px solid #2a2a35;
            border-radius: 6px;
            padding: 10px;
            font-size: 11px;
            font-family: monospace;
            color: #7ecfff;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 6px;
        }
        /* Timeline grid */
        #wr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 3px;
            margin-top: 12px;
        }
        .wr-thumb {
            position: relative;
            cursor: pointer;
            border-radius: 4px;
            overflow: hidden;
            background: #111;
            border: 2px solid transparent;
            transition: border-color .15s;
        }
        .wr-thumb img {
            width: 100%;
            display: block;
            opacity: .75;
            transition: opacity .2s;
        }
        .wr-thumb:hover img { opacity: 1; }
        .wr-thumb .wr-thumb-ts {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: rgba(0,0,0,.7);
            color: #fff;
            font-size: 10px;
            text-align: center;
            padding: 2px 0;
            font-family: monospace;
        }
        .wr-thumb.seg-in  { border-color: #e8431a; }
        .wr-thumb.seg-in img { opacity: 1; }
        .wr-thumb.seg-pend { border-color: #2ecc71; }
        .wr-thumb .wr-thumb-action {
            position: absolute;
            top: 3px; right: 3px;
            background: rgba(0,0,0,.65);
            color: #fff;
            font-size: 9px;
            padding: 1px 4px;
            border-radius: 3px;
            display: none;
            font-family: monospace;
        }
        .wr-thumb:hover .wr-thumb-action { display: block; }
    `;
    document.head.appendChild(css);

    // ── Build UI ──────────────────────────────────────────────────────────────
    const container = document.createElement('div');
    container.id = 'wr-compiler';
    container.innerHTML = `
        <h2>
            ✂️ Segment Compiler
            <span class="badge">FFmpeg</span>
            <span style="margin-left:auto;font-size:11px;color:#555;">${totalDuration ? fmtDisplay(totalDuration) : '??:??'} total</span>
        </h2>

        <div id="wr-quality-row">
            <label>Qualité :</label>
            <select id="wr-quality-select"></select>
        </div>

        <div id="wr-bar-wrap">
            <div id="wr-bar-hover"></div>
            <div id="wr-bar"></div>
            <div id="wr-bar-legend">
                <span>0:00</span>
                <span id="wr-bar-mid"></span>
                <span>${totalDuration ? fmtDisplay(totalDuration) : '??'}</span>
            </div>
        </div>

        <div id="wr-controls">
            <button class="wr-btn start" id="wr-btn-start">◀ Clic gauche : Début</button>
            <button class="wr-btn end"   id="wr-btn-end">Fin : Clic droit ▶</button>
            <button class="wr-btn"       id="wr-btn-clear">🗑 Tout effacer</button>
            <span id="wr-pending-info"></span>
        </div>

        <div id="wr-segments"></div>

        <div id="wr-bottom-row" style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="wr-btn primary" id="wr-btn-gen">⚙ Générer FFmpeg</button>
            <button class="wr-btn copy hidden" id="wr-btn-copy">📋 Copier commande</button>
        </div>

        <div id="wr-ffmpeg-wrap">
            <div id="wr-ffmpeg-out"></div>
        </div>

        <div id="wr-grid"></div>
    `;

    // Insérer AVANT .video-info
    const videoInfo = document.querySelector('.video-info');
    if (videoInfo) {
        videoInfo.parentNode.insertBefore(container, videoInfo);
    } else {
        document.body.insertBefore(container, document.body.firstChild);
    }

    // ── Qualité ───────────────────────────────────────────────────────────────
    const qSelect = document.getElementById('wr-quality-select');
    if (qualities.length) {
        qualities.forEach((q, i) => {
            const opt = document.createElement('option');
            opt.value  = q.url;
            opt.textContent = q.label;
            qSelect.appendChild(opt);
        });
        qSelect.selectedIndex = qSelect.options.length - 1; // meilleure qualité par défaut
    } else {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'URL non détectée';
        qSelect.appendChild(opt);
    }

    // ── State ─────────────────────────────────────────────────────────────────
    let segments    = [];
    let pendingStart = null;

    // ── Helpers état ──────────────────────────────────────────────────────────
    function currentVideoUrl() {
        return qSelect.value || (qualities[0] && qualities[0].url) || '';
    }

    function updatePendingInfo() {
        const el = document.getElementById('wr-pending-info');
        if (pendingStart !== null) {
            el.textContent = `▶ Début : ${fmtDisplay(pendingStart)} — clic droit pour fixer la fin`;
        } else {
            el.textContent = '';
        }
        refreshHighlights();
    }

    function renderSegments() {
        const list = document.getElementById('wr-segments');
        list.innerHTML = '';
        segments.forEach((seg, i) => {
            const dur = seg.end - seg.start;
            const div = document.createElement('div');
            div.className = 'wr-seg-item';
            div.innerHTML = `
                <span>#${i + 1} &nbsp; ${fmtDisplay(seg.start)} → ${fmtDisplay(seg.end)}</span>
                <span class="wr-seg-dur">(${fmtDisplay(dur)})</span>
                <span class="wr-seg-del" data-i="${i}" title="Supprimer">✕</span>
            `;
            list.appendChild(div);
        });
        list.querySelectorAll('.wr-seg-del').forEach(btn => {
            btn.addEventListener('click', () => {
                segments.splice(parseInt(btn.dataset.i), 1);
                renderSegments();
                refreshHighlights();
                refreshFFmpegWrap();
            });
        });
        refreshHighlights();
    }

    function refreshHighlights() {
        // Barre
        const bar = document.getElementById('wr-bar');
        const dur = totalDuration || 1;
        bar.innerHTML = '';
        segments.forEach(seg => {
            const box = document.createElement('div');
            box.className = 'wr-seg-box';
            box.style.left  = (seg.start / dur * 100) + '%';
            box.style.width = ((seg.end - seg.start) / dur * 100) + '%';
            bar.appendChild(box);
        });
        if (pendingStart !== null) {
            const line = document.createElement('div');
            line.className = 'wr-pending-line';
            line.style.left = (pendingStart / dur * 100) + '%';
            bar.appendChild(line);
        }
        // Miniatures grid
        document.querySelectorAll('.wr-thumb').forEach(th => {
            const sec = parseFloat(th.dataset.sec);
            th.classList.remove('seg-in', 'seg-pend');
            if (segments.some(s => sec >= s.start && sec < s.end)) {
                th.classList.add('seg-in');
            }
            if (pendingStart !== null && sec === pendingStart) {
                th.classList.add('seg-pend');
            }
        });
    }

    function refreshFFmpegWrap() {
        const wrap = document.getElementById('wr-ffmpeg-wrap');
        const btn  = document.getElementById('wr-btn-copy');
        if (segments.length === 0) {
            wrap.style.display = 'none';
            btn.classList.add('hidden');
        }
    }

    // ── Barre timeline ────────────────────────────────────────────────────────
    const bar     = document.getElementById('wr-bar');
    const hoverEl = document.getElementById('wr-bar-hover');
    const midEl   = document.getElementById('wr-bar-mid');

    if (totalDuration && midEl) {
        midEl.textContent = fmtDisplay(totalDuration / 2);
    }

    function barTime(e) {
        const rect = bar.getBoundingClientRect();
        const x    = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
        return Math.floor((x / rect.width) * (totalDuration || 1));
    }

    bar.addEventListener('mousemove', e => {
        const t   = barTime(e);
        const pct = (t / (totalDuration || 1)) * 100;
        hoverEl.textContent  = fmtDisplay(t);
        hoverEl.style.left   = pct + '%';
        hoverEl.style.display = 'block';
    });

    bar.addEventListener('mouseleave', () => {
        hoverEl.style.display = 'none';
    });

    bar.addEventListener('mousedown', e => {
        e.preventDefault();
        const t = barTime(e);
        if (e.button === 0) {
            pendingStart = t;
            updatePendingInfo();
        } else if (e.button === 2) {
            if (pendingStart === null) { alert('Clic gauche d\'abord pour fixer le début.'); return; }
            let end = t;
            if (end < pendingStart) [pendingStart, end] = [end, pendingStart];
            if (end === pendingStart) end = Math.min(pendingStart + 5, totalDuration || pendingStart + 5);
            segments.push({ start: pendingStart, end });
            pendingStart = null;
            renderSegments();
            updatePendingInfo();
        }
    });

    bar.addEventListener('contextmenu', e => e.preventDefault());

    // ── Boutons controls ──────────────────────────────────────────────────────
    document.getElementById('wr-btn-start').addEventListener('click', () => {
        const v = document.querySelector('video');
        if (v) { pendingStart = Math.floor(v.currentTime); updatePendingInfo(); }
        else alert('Aucune balise <video> détectée sur la page.');
    });

    document.getElementById('wr-btn-end').addEventListener('click', () => {
        const v = document.querySelector('video');
        if (!v) { alert('Aucune balise <video> détectée.'); return; }
        if (pendingStart === null) { alert('Clic gauche d\'abord pour fixer le début.'); return; }
        let end = Math.floor(v.currentTime);
        if (end < pendingStart) [pendingStart, end] = [end, pendingStart];
        segments.push({ start: pendingStart, end });
        pendingStart = null;
        renderSegments();
        updatePendingInfo();
    });

    document.getElementById('wr-btn-clear').addEventListener('click', () => {
        if (segments.length && !confirm('Effacer tous les segments ?')) return;
        segments    = [];
        pendingStart = null;
        renderSegments();
        updatePendingInfo();
        refreshFFmpegWrap();
    });

    // ── Génération FFmpeg ─────────────────────────────────────────────────────
    // L'URL passée à FFmpeg est celle du proxy local (localhost:2002/wr/proxy.php)
    // Le proxy ajoute Referer + cookies et forward les Range requests.
    // → FFmpeg n'accède JAMAIS directement à whoreshub.com.
    function buildFFmpegCommand(videoUrl, segs) {
        if (!segs.length) return '';

        const pUrl   = proxyUrl(videoUrl);              // URL proxy locale
        const pSafe  = pUrl.replace(/"/g, '\\"');       // escape guillemets
        const title  = safeFilename(getVideoTitle());
        const output = `"${title}.mp4"`;

        if (segs.length === 1) {
            const s = segs[0];
            return `ffmpeg -ss ${fmtFFmpeg(s.start)} -to ${fmtFFmpeg(s.end)} -i "${pSafe}" -c copy ${output}`;
        }

        // Plusieurs segments → concat demuxer avec un seul input réutilisé
        // (re-seek via -ss/-to avant chaque -i → Range request différent par segment)
        const inputs   = segs.map(s =>
            `-ss ${fmtFFmpeg(s.start)} -to ${fmtFFmpeg(s.end)} -i "${pSafe}"`
        ).join(' \\\n       ');
        const concatIn = segs.map((_, i) => `[${i}:v][${i}:a]`).join('');
        const n        = segs.length;
        const filter   = `"${concatIn}concat=n=${n}:v=1:a=1[outv][outa]"`;

        return (
            `ffmpeg \\\n` +
            `       ${inputs} \\\n` +
            `       -filter_complex ${filter} \\\n` +
            `       -map "[outv]" -map "[outa]" \\\n` +
            `       -c:v libx264 -preset fast -c:a aac \\\n` +
            `       ${output}`
        );
    }

    document.getElementById('wr-btn-gen').addEventListener('click', () => {
        if (!segments.length) { alert('Aucun segment défini.'); return; }
        const url = currentVideoUrl();
        if (!url) { alert("Aucune URL vidéo détectée dans les flashvars."); return; }

        const cmd  = buildFFmpegCommand(url, segments);
        const wrap = document.getElementById('wr-ffmpeg-wrap');
        const out  = document.getElementById('wr-ffmpeg-out');
        const btn  = document.getElementById('wr-btn-copy');

        out.textContent    = cmd;
        wrap.style.display = 'block';
        btn.classList.remove('hidden');
    });

    document.getElementById('wr-btn-copy').addEventListener('click', () => {
        const text = document.getElementById('wr-ffmpeg-out').textContent;
        if (!text) return;
        try {
            GM_setClipboard(text);
            const btn = document.getElementById('wr-btn-copy');
            btn.textContent = '✅ Copié !';
            setTimeout(() => { btn.textContent = '📋 Copier commande'; }, 2000);
        } catch (e) {
            navigator.clipboard.writeText(text).catch(() => {});
        }
    });

    // ── Timeline grid de miniatures ───────────────────────────────────────────
    if (!timelineCount && !timelineUrlTpl) {
        // Pas de count dans les flashvars — on sonde jusqu'au 404
        const grid = document.getElementById('wr-grid');
        grid.innerHTML = '<span style="font-size:12px;color:#555;padding:8px 0;display:block;">Sondage des miniatures timeline…</span>';

        (async function probeTimeline() {
            const found = [];
            const BATCH = 8;
            let n    = 1;
            let stop = false;

            while (!stop && found.length < 300) {
                const batch = Array.from({ length: BATCH }, (_, i) => n + i);
                const results = await Promise.all(batch.map(idx => new Promise(res => {
                    GM_xmlhttpRequest({
                        method: 'HEAD',
                        url: thumbUrl(idx),
                        onload:  r => res(r.status === 200 || r.status === 206),
                        onerror: () => res(false),
                    });
                })));
                for (let i = 0; i < BATCH; i++) {
                    if (results[i]) found.push(n + i);
                    else { stop = true; break; }
                }
                n += BATCH;
            }

            timelineCount = found.length;
            grid.innerHTML = '';
            if (!found.length) {
                grid.innerHTML = '<span style="font-size:12px;color:#555;">Aucune miniature timeline trouvée.</span>';
                return;
            }
            buildGrid(found.length);
        })();
    } else {
        buildGrid(timelineCount);
    }

    function buildGrid(count) {
        const grid = document.getElementById('wr-grid');
        grid.innerHTML = '';
        const dur = totalDuration || (count * timelineInterval) || 1;

        for (let i = 1; i <= count; i++) {
            const sec = Math.round((i - 1) / count * dur); // répartition uniforme si pas d'interval
            const secActual = timelineInterval ? (i - 1) * timelineInterval : sec;

            const wrap = document.createElement('div');
            wrap.className = 'wr-thumb';
            wrap.dataset.sec = secActual;

            const action = pendingStart !== null ? 'Fin →' : '← Début';
            wrap.innerHTML = `
                <img src="${thumbUrl(i)}" loading="lazy" onerror="this.closest('.wr-thumb').style.display='none'" />
                <div class="wr-thumb-ts">${fmtDisplay(secActual)}</div>
                <div class="wr-thumb-action">${action}</div>
            `;

            wrap.addEventListener('click', e => {
                e.preventDefault();
                pendingStart = secActual;
                updatePendingInfo();
                // Mettre à jour le label action sur toutes les thumbs
                document.querySelectorAll('.wr-thumb-action').forEach(a => { a.textContent = 'Fin →'; });
            });

            wrap.addEventListener('contextmenu', e => {
                e.preventDefault();
                if (pendingStart === null) { alert('Clic gauche d\'abord pour fixer le début.'); return; }
                let end = secActual;
                if (end < pendingStart) [pendingStart, end] = [end, pendingStart];
                if (end === pendingStart) end = Math.min(pendingStart + (timelineInterval || 5), totalDuration || pendingStart + 5);
                segments.push({ start: pendingStart, end });
                pendingStart = null;
                renderSegments();
                updatePendingInfo();
                document.querySelectorAll('.wr-thumb-action').forEach(a => { a.textContent = '← Début'; });
            });

            grid.appendChild(wrap);
        }
    }

})();
