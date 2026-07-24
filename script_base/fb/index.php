<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Facebook Video Extractor</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: #0d0d0d;
  color: #e0e0e0;
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  padding: 30px 20px;
}

h1 {
  text-align: center;
  font-size: 22px;
  font-weight: 600;
  color: #fff;
  margin-bottom: 28px;
}
h1 span { color: #1877f2; }

/* ── Cards ── */
.card {
  background: #1a1a1a;
  border: 1px solid #2a2a2a;
  border-radius: 10px;
  padding: 20px 24px;
  max-width: 860px;
  margin: 0 auto 20px;
}

/* ── Input row ── */
.url-row { display: flex; gap: 10px; }
#url-input {
  flex: 1;
  background: #111;
  border: 1px solid #333;
  border-radius: 6px;
  color: #fff;
  padding: 10px 14px;
  font-size: 14px;
  outline: none;
  transition: border-color .2s;
}
#url-input:focus { border-color: #1877f2; }
#url-input::placeholder { color: #555; }

button {
  background: #1877f2;
  border: none;
  border-radius: 6px;
  color: #fff;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  padding: 9px 18px;
  transition: background .2s;
  white-space: nowrap;
}
button:hover { background: #166ee1; }
button:disabled { background: #333; color: #666; cursor: default; }
button.ghost {
  background: #252525;
  border: 1px solid #333;
  font-weight: 500;
}
button.ghost:hover { background: #2f2f2f; }
button.copied { background: #1a4a1a !important; color: #2ecc71 !important; border-color: #2ecc71 !important; }

/* ── Status ── */
#status {
  margin-top: 12px;
  font-size: 13px;
  color: #888;
  min-height: 18px;
}
#status.err { color: #e74c3c; }
#status.ok  { color: #2ecc71; }

/* ── Spinner ── */
.spin {
  display: inline-block;
  width: 12px; height: 12px;
  border: 2px solid #333;
  border-top-color: #1877f2;
  border-radius: 50%;
  animation: spin .7s linear infinite;
  vertical-align: middle;
  margin-right: 6px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Section titles ── */
.section-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #555;
  margin-bottom: 10px;
}

/* ── Badges ── */
.badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 3px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
  vertical-align: middle;
}
.badge-hd    { background: #1a3a5c; color: #5b9bd5; border: 1px solid #1e4a78; }
.badge-sd    { background: #1a3a1a; color: #5bd55b; border: 1px solid #1e781e; }
.badge-dash  { background: #2a1a3a; color: #a05bd5; border: 1px solid #441e78; }
.badge-audio { background: #3a2a10; color: #d5a05b; border: 1px solid #785020; }
.badge-live  { background: #3a0d0d; color: #ff5555; border: 1px solid #7a1515; animation: pulse-badge 1.5s ease-in-out infinite; }
.badge-sub   { background: #1a3030; color: #5bd5d5; border: 1px solid #1e7878; }
.badge-auto  { background: #2a2a10; color: #d5d55b; border: 1px solid #787820; }
@keyframes pulse-badge { 0%,100%{opacity:1} 50%{opacity:.6} }

/* ── Owner row ── */
.owner-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}
.owner-avatar {
  width: 44px; height: 44px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #333;
  background: #222;
  flex-shrink: 0;
}
.owner-name { font-size: 15px; font-weight: 600; color: #fff; }
.owner-link { font-size: 11px; color: #1877f2; text-decoration: none; }
.owner-link:hover { text-decoration: underline; }

/* ── Meta title ── */
.meta-title {
  font-size: 15px;
  font-weight: 600;
  color: #fff;
  line-height: 1.4;
  margin-bottom: 10px;
}

/* ── Stats row ── */
.stats-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 14px;
}
.stat {
  font-size: 12px;
  color: #888;
}
.stat strong { color: #ccc; }
.stat.live-viewers strong { color: #ff5555; }

/* ── Description ── */
.desc-toggle {
  font-size: 12px;
  color: #1877f2;
  cursor: pointer;
  background: none;
  border: none;
  padding: 0;
  font-weight: 500;
  display: inline-block;
  margin-bottom: 8px;
}
.desc-toggle:hover { text-decoration: underline; background: none; }
.desc-box {
  font-size: 13px;
  color: #aaa;
  line-height: 1.6;
  white-space: pre-wrap;
  background: #111;
  border: 1px solid #222;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 14px;
  display: none;
}

/* ── Thumbnails scroll ── */
.thumbs-scroll {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 8px;
  scroll-snap-type: x mandatory;
}
.thumbs-scroll::-webkit-scrollbar { height: 4px; }
.thumbs-scroll::-webkit-scrollbar-track { background: #111; border-radius: 4px; }
.thumbs-scroll::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
.thumb-item {
  position: relative;
  flex-shrink: 0;
  scroll-snap-align: start;
  width: 240px;
}
.thumb-item img {
  width: 100%;
  border-radius: 6px;
  display: block;
  border: 1px solid #2a2a2a;
  background: #111;
  cursor: pointer;
}
.thumb-item img:hover { border-color: #1877f2; }
.thumb-caption {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: rgba(0,0,0,.7);
  color: #ccc;
  font-size: 10px;
  text-align: center;
  padding: 3px 0;
  border-radius: 0 0 6px 6px;
  pointer-events: none;
}

/* ── URL boxes (formats) ── */
.url-box {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
}
.url-box input {
  flex: 1;
  background: #111;
  border: 1px solid #2a2a2a;
  border-radius: 5px;
  color: #aaa;
  font-family: monospace;
  font-size: 11px;
  padding: 7px 10px;
  outline: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.url-box input:focus { border-color: #1877f2; }
.format-label {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
  font-size: 12px;
  color: #999;
}

/* ── DASH table ── */
.dash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.dash-table th {
  text-align: left;
  color: #555;
  font-weight: 600;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 6px 8px;
  border-bottom: 1px solid #222;
}
.dash-table td {
  padding: 7px 8px;
  border-bottom: 1px solid #1a1a1a;
  color: #aaa;
  vertical-align: middle;
}
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tr:hover td { background: #1f1f1f; }
.dash-table code {
  font-family: monospace;
  font-size: 11px;
  color: #888;
  background: #111;
  padding: 1px 5px;
  border-radius: 3px;
}
.dash-table .col-url input {
  background: #111;
  border: 1px solid #222;
  border-radius: 4px;
  color: #666;
  font-family: monospace;
  font-size: 10px;
  padding: 4px 7px;
  width: 100%;
  outline: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dash-table .col-url { width: 200px; min-width: 120px; }
.dash-table button {
  padding: 4px 9px;
  font-size: 11px;
}

/* ── Collapsible section ── */
.collapsible-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  user-select: none;
}
.collapsible-header:hover .section-title { color: #888; }
.collapsible-arrow {
  font-size: 14px;
  color: #444;
  transition: transform .2s;
}
.collapsible-header.open .collapsible-arrow { transform: rotate(90deg); }
.collapsible-body { display: none; margin-top: 12px; }
.collapsible-body.open { display: block; }

/* ── Captions ── */
.caption-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  font-size: 12px;
  color: #aaa;
}
.caption-row a { color: #1877f2; text-decoration: none; font-size: 11px; }
.caption-row a:hover { text-decoration: underline; }

/* ── JSON section ── */
.json-row {
  display: flex;
  gap: 8px;
  align-items: center;
}
.json-row input {
  flex: 1;
  background: #111;
  border: 1px solid #2a2a2a;
  border-radius: 5px;
  color: #666;
  font-family: monospace;
  font-size: 11px;
  padding: 7px 10px;
  outline: none;
}

hr.divider { border: none; border-top: 1px solid #1e1e1e; margin: 18px 0; }

/* ── Source badge ── */
.source-tag {
  font-size: 10px;
  color: #444;
  float: right;
  margin-top: 2px;
}
</style>
</head>
<body>

<h1><span>Facebook</span> Video Extractor</h1>

<!-- Input -->
<div class="card">
  <div class="url-row">
    <input id="url-input" type="text"
           placeholder="URL Facebook : vidéo, reel, live… (share/v/, watch?v=, /videos/, fb.watch/…)" />
    <button id="btn-fetch" onclick="fetchVideo()">Extraire</button>
  </div>
  <div id="status"></div>
</div>

<!-- Result -->
<div class="card" id="result" style="display:none">

  <!-- Owner -->
  <div class="owner-row" id="owner-row" style="display:none">
    <img id="owner-avatar" class="owner-avatar" src="" alt=""
         onerror="this.style.display='none'" />
    <div>
      <div class="owner-name" id="owner-name"></div>
      <a id="owner-link" class="owner-link" href="#" target="_blank"></a>
    </div>
  </div>

  <!-- Title + badges -->
  <div class="meta-title" id="meta-title"></div>

  <!-- Stats -->
  <div class="stats-row" id="stats-row"></div>

  <!-- Description -->
  <button class="desc-toggle" id="desc-toggle" style="display:none"
          onclick="toggleDesc()">▸ Description</button>
  <div class="desc-box" id="desc-box"></div>

  <!-- Miniatures -->
  <div id="thumbs-section" style="display:none">
    <div class="section-title">Miniatures</div>
    <div class="thumbs-scroll" id="thumbs-scroll"></div>
  </div>

  <hr class="divider" id="div1" style="display:none">

  <!-- Formats progressifs (HD/SD) — prominent -->
  <div id="prog-section" style="display:none">
    <div class="section-title">Téléchargement direct</div>
    <div id="prog-list"></div>
  </div>

  <!-- Live streams -->
  <div id="live-section" style="display:none">
    <div class="section-title" style="color:#ff5555">🔴 Flux en direct</div>
    <div id="live-list"></div>
  </div>

  <hr class="divider" id="div2" style="display:none">

  <!-- DASH vidéo -->
  <div id="dashv-wrap" style="display:none">
    <div class="collapsible-header" onclick="toggleCollapse('dashv')">
      <div class="section-title">DASH Vidéo <span id="dashv-count" class="badge badge-dash"></span></div>
      <span class="collapsible-arrow">▶</span>
    </div>
    <div class="collapsible-body" id="dashv-body">
      <table class="dash-table" id="dashv-table">
        <thead><tr>
          <th>Résolution</th><th>Codec</th><th>Bitrate</th><th>FPS</th><th>URL</th><th></th>
        </tr></thead>
        <tbody id="dashv-tbody"></tbody>
      </table>
    </div>
  </div>

  <hr class="divider" id="div3" style="display:none">

  <!-- DASH audio -->
  <div id="dasha-wrap" style="display:none">
    <div class="collapsible-header" onclick="toggleCollapse('dasha')">
      <div class="section-title">DASH Audio <span id="dasha-count" class="badge badge-audio"></span></div>
      <span class="collapsible-arrow">▶</span>
    </div>
    <div class="collapsible-body" id="dasha-body">
      <table class="dash-table" id="dasha-table">
        <thead><tr>
          <th>Codec</th><th>Débit</th><th>Fréquence</th><th>URL</th><th></th>
        </tr></thead>
        <tbody id="dasha-tbody"></tbody>
      </table>
    </div>
  </div>

  <hr class="divider" id="div4" style="display:none">

  <!-- Captions -->
  <div id="captions-wrap" style="display:none">
    <div class="collapsible-header" onclick="toggleCollapse('captions')">
      <div class="section-title">Sous-titres / Captions <span id="caps-count" class="badge badge-sub"></span></div>
      <span class="collapsible-arrow">▶</span>
    </div>
    <div class="collapsible-body" id="captions-body">
      <div id="captions-list"></div>
    </div>
  </div>

  <hr class="divider" id="div5" style="display:none">

  <!-- JSON -->
  <div id="json-section" style="display:none">
    <div class="section-title">Données JSON (yt-dlp complet)</div>
    <div class="json-row">
      <input id="json-path" type="text" readonly />
      <a id="json-link" href="#" target="_blank"><button type="button" class="ghost">Ouvrir</button></a>
      <a id="json-dl"   href="#" download><button type="button" class="ghost">Télécharger</button></a>
    </div>
  </div>

</div><!-- /result -->

<script>
const $ = id => document.getElementById(id);

/* ── Status ── */
function setStatus(msg, cls = '') {
  const el = $('status');
  el.className = cls;
  el.innerHTML = msg;
}

/* ── Formatage ── */
function fmtNum(n) {
  if (n == null) return null;
  if (n >= 1e6) return (n/1e6).toFixed(1).replace(/\.0$/, '') + ' M';
  if (n >= 1e3) return (n/1e3).toFixed(1).replace(/\.0$/, '') + ' k';
  return n.toString();
}
function fmtBitrate(kbps) {
  if (!kbps) return '—';
  return kbps >= 1000 ? (kbps/1000).toFixed(2) + ' Mbps' : Math.round(kbps) + ' kbps';
}
function fmtFreq(hz) {
  if (!hz) return '—';
  return hz >= 1000 ? (hz/1000).toFixed(1) + ' kHz' : hz + ' Hz';
}

/* ── Copy helper ── */
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓';
    btn.classList.add('copied');
    setTimeout(() => { btn.textContent = orig; btn.classList.remove('copied'); }, 1500);
  });
}

/* ── Collapsible sections ── */
function toggleCollapse(id) {
  const header = document.querySelector(`#${id}-wrap .collapsible-header`);
  const body   = $(`${id}-body`);
  header.classList.toggle('open');
  body.classList.toggle('open');
}

/* ── Description toggle ── */
function toggleDesc() {
  const box = $('desc-box');
  const btn = $('desc-toggle');
  const open = box.style.display === 'block';
  box.style.display = open ? 'none' : 'block';
  btn.textContent   = (open ? '▸' : '▾') + ' Description';
}

/* ── Main fetch ── */
async function fetchVideo() {
  const url = $('url-input').value.trim();
  if (!url) { setStatus('Collez une URL Facebook.', 'err'); return; }

  $('btn-fetch').disabled = true;
  $('result').style.display = 'none';
  setStatus('<span class="spin"></span>Extraction en cours via yt-dlp…');

  try {
    const res  = await fetch('api.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ url }),
    });
    const json = await res.json();

    if (!json.success) {
      setStatus('Erreur : ' + (json.error || 'inconnue'), 'err');
      return;
    }

    renderResult(json.data);
    setStatus('Extraction réussie' + (json.data.source === 'ytdlp' ? ' (yt-dlp)' : ' (scraping HTML)') + '.', 'ok');

  } catch (e) {
    setStatus('Erreur réseau : ' + e.message, 'err');
  } finally {
    $('btn-fetch').disabled = false;
  }
}

/* ── Render ── */
function renderResult(d) {
  /* Owner */
  if (d.uploader || d.uploader_url) {
    if (d.thumbnail) $('owner-avatar').src = d.thumbnail;
    else             $('owner-avatar').style.display = 'none';
    $('owner-name').textContent = d.uploader || '';
    if (d.uploader_url) {
      $('owner-link').href        = d.uploader_url;
      $('owner-link').textContent = d.uploader_url.replace(/^https?:\/\/(www\.)?facebook\.com\//, 'fb.com/');
    }
    $('owner-row').style.display = 'flex';
  } else {
    $('owner-row').style.display = 'none';
  }

  /* Title */
  const liveTag = d.is_live ? ' <span class="badge badge-live">🔴 Live</span>' : '';
  $('meta-title').innerHTML = escHtml(d.title || 'Vidéo Facebook') + liveTag;

  /* Stats */
  const stats = [];
  if (d.is_live && d.concurrent_view_count != null)
    stats.push(`<span class="stat live-viewers"><strong>${fmtNum(d.concurrent_view_count)}</strong> spectateurs</span>`);
  if (d.view_count != null)
    stats.push(`<span class="stat"><strong>${fmtNum(d.view_count)}</strong> vues</span>`);
  if (d.like_count != null)
    stats.push(`<span class="stat"><strong>${fmtNum(d.like_count)}</strong> j'aime</span>`);
  if (d.duration_string)
    stats.push(`<span class="stat">⏱ <strong>${escHtml(d.duration_string)}</strong></span>`);
  else if (d.duration_sec)
    stats.push(`<span class="stat">⏱ <strong>${fmtDur(d.duration_sec)}</strong></span>`);
  if (d.upload_date)
    stats.push(`<span class="stat">📅 <strong>${escHtml(d.upload_date)}</strong></span>`);
  if (d.video_id)
    stats.push(`<span class="stat">ID <strong>${escHtml(d.video_id)}</strong></span>`);
  $('stats-row').innerHTML = stats.join('');

  /* Description */
  if (d.description) {
    $('desc-box').textContent    = d.description;
    $('desc-toggle').style.display = '';
  } else {
    $('desc-toggle').style.display = 'none';
    $('desc-box').style.display    = 'none';
  }

  /* Thumbnails */
  const thumbs = d.thumbnails || (d.thumbnail ? [d.thumbnail] : []);
  if (thumbs.length) {
    const scroll = $('thumbs-scroll');
    scroll.innerHTML = '';
    thumbs.forEach((url, i) => {
      const div = document.createElement('div');
      div.className = 'thumb-item';
      div.innerHTML = `<img src="${escAttr(url)}" alt="miniature ${i+1}"
                            onerror="this.closest('.thumb-item').remove()"
                            onclick="window.open('${escAttr(url)}','_blank')" />
                       <div class="thumb-caption">#${i+1}</div>`;
      scroll.appendChild(div);
    });
    $('thumbs-section').style.display = '';
    $('div1').style.display = '';
  } else {
    $('thumbs-section').style.display = 'none';
  }

  /* Progressive formats */
  const prog = d.formats?.progressive || [];
  if (prog.length) {
    const list = $('prog-list');
    list.innerHTML = '';
    prog.forEach(f => {
      const badgeClass = f.id === 'hd' ? 'badge-hd' : 'badge-sd';
      const info = [
        f.width && f.height ? `${f.width}×${f.height}` : '',
        f.tbr ? fmtBitrate(f.tbr) : '',
      ].filter(Boolean).join(' · ');

      list.insertAdjacentHTML('beforeend', `
        <div class="format-label">
          <span class="badge ${badgeClass}">${escHtml(f.label)}</span>
          ${info ? `<span style="color:#666;font-size:11px">${escHtml(info)}</span>` : ''}
          <span style="color:#444;font-size:11px">.${escHtml(f.ext)}</span>
        </div>
        <div class="url-box">
          <input type="text" readonly value="${escAttr(f.url)}" id="prog-${f.id}" />
          <button onclick="copyText($('prog-${f.id}').value, this)">Copier</button>
          <button class="ghost" onclick="window.open($('prog-${f.id}').value,'_blank')">Ouvrir</button>
        </div>`);
    });
    $('prog-section').style.display = '';
  } else {
    $('prog-section').style.display = 'none';
  }

  /* Live streams */
  const live = d.formats?.live || [];
  if (live.length) {
    const list = $('live-list');
    list.innerHTML = '';
    live.forEach((f, i) => {
      const id = `live-${i}`;
      list.insertAdjacentHTML('beforeend', `
        <div class="url-box">
          <input type="text" readonly value="${escAttr(f.url)}" id="${id}" />
          <span style="color:#555;font-size:11px">${escHtml(f.protocol || f.ext)} ${f.resolution ? '· '+f.resolution : ''}</span>
          <button onclick="copyText($('${id}').value, this)">Copier</button>
        </div>`);
    });
    $('live-section').style.display = '';
    $('div2').style.display = '';
  } else {
    $('live-section').style.display = 'none';
  }

  /* DASH vidéo */
  const dv = d.formats?.dash_video || [];
  if (dv.length) {
    const tbody = $('dashv-tbody');
    tbody.innerHTML = '';
    dv.forEach((f, i) => {
      const id = `dv-${i}`;
      tbody.insertAdjacentHTML('beforeend', `<tr>
        <td>${f.resolution || (f.width ? `${f.width}×${f.height}` : '—')}</td>
        <td><code>${escHtml(f.vcodec || '—')}</code></td>
        <td>${fmtBitrate(f.tbr)}</td>
        <td>${f.fps || '—'}</td>
        <td class="col-url"><input type="text" readonly value="${escAttr(f.url)}" id="${id}" /></td>
        <td><button onclick="copyText($('${id}').value, this)">⧉</button></td>
      </tr>`);
    });
    $('dashv-count').textContent = dv.length;
    $('dashv-wrap').style.display = '';
    $('div2').style.display = '';
    $('div3').style.display = '';
  } else {
    $('dashv-wrap').style.display = 'none';
  }

  /* DASH audio */
  const da = d.formats?.dash_audio || [];
  if (da.length) {
    const tbody = $('dasha-tbody');
    tbody.innerHTML = '';
    da.forEach((f, i) => {
      const id = `da-${i}`;
      tbody.insertAdjacentHTML('beforeend', `<tr>
        <td><code>${escHtml(f.acodec || '—')}</code></td>
        <td>${fmtBitrate(f.abr)}</td>
        <td>${fmtFreq(f.asr)}</td>
        <td class="col-url"><input type="text" readonly value="${escAttr(f.url)}" id="${id}" /></td>
        <td><button onclick="copyText($('${id}').value, this)">⧉</button></td>
      </tr>`);
    });
    $('dasha-count').textContent = da.length;
    $('dasha-wrap').style.display = '';
    $('div4').style.display = '';
  } else {
    $('dasha-wrap').style.display = 'none';
  }

  /* Captions */
  const caps = d.captions || [];
  if (caps.length) {
    const list = $('captions-list');
    list.innerHTML = '';
    caps.forEach(c => {
      const typeTag = c.type === 'auto'
        ? '<span class="badge badge-auto">Auto</span>'
        : '<span class="badge badge-sub">Manuels</span>';
      list.insertAdjacentHTML('beforeend', `
        <div class="caption-row">
          ${typeTag}
          <strong style="color:#ccc">${escHtml(c.lang)}</strong>
          <span style="color:#555">${escHtml(c.name || '')} · .${escHtml(c.ext)}</span>
          <a href="${escAttr(c.url)}" target="_blank">Ouvrir</a>
          <a href="${escAttr(c.url)}" download>Télécharger</a>
        </div>`);
    });
    $('caps-count').textContent = caps.length;
    $('captions-wrap').style.display = '';
    $('div5').style.display = '';
  } else {
    $('captions-wrap').style.display = 'none';
  }

  /* JSON */
  if (d.json_file) {
    $('json-path').value = d.json_file;
    $('json-link').href  = d.json_file;
    $('json-dl').href    = d.json_file;
    $('json-section').style.display = '';
  } else {
    $('json-section').style.display = 'none';
  }

  $('result').style.display = 'block';
}

/* ── Utils ── */
function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
  return String(s ?? '').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function fmtDur(sec) {
  const h = Math.floor(sec/3600), m = Math.floor((sec%3600)/60), s = Math.round(sec%60);
  if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  return `${m}:${String(s).padStart(2,'0')}`;
}

$('url-input').addEventListener('keydown', e => { if (e.key === 'Enter') fetchVideo(); });
</script>
</body>
</html>
