<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>YouTube Scraper</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.frow:hover{background:rgba(255,255,255,.04)}
.thumb-card img{transition:transform .2s}
.thumb-card:hover img{transform:scale(1.03)}
.source-ytdlp{background:#16a34a}
.source-api{background:#2563eb}
.source-html{background:#d97706}
</style>
</head>
<body class="bg-slate-900 min-h-screen p-4">
<div class="max-w-4xl mx-auto space-y-4">

  <!-- Header -->
  <div class="bg-gradient-to-r from-red-700 to-red-500 rounded-2xl p-5 text-center shadow-xl">
    <h1 class="text-2xl font-bold text-white"><i class="fab fa-youtube mr-2"></i>YouTube Scraper</h1>
    <p class="text-red-100 text-sm mt-1">yt-dlp → Android API → HTML fallback · formats + toutes les images</p>
  </div>

  <!-- Input -->
  <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-5 space-y-3">
    <div class="flex gap-2">
      <input type="url" id="urlInput" placeholder="https://www.youtube.com/watch?v=..."
        class="flex-1 bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all" />
      <button onclick="scrape()" id="btnScrape"
        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition-all flex items-center gap-2 min-w-[130px] justify-center">
        <i class="fas fa-search"></i><span>Extraire</span>
      </button>
    </div>

    <div id="progress" class="hidden space-y-1">
      <div class="flex justify-between text-xs text-slate-400">
        <span id="progressLabel">Progression</span><span id="progressText">0%</span>
      </div>
      <div class="w-full bg-slate-700 rounded-full h-1.5">
        <div id="progressBar" class="bg-red-500 h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
      </div>
    </div>

    <div class="bg-slate-950 rounded-lg p-3 font-mono text-xs text-green-400 h-28 overflow-y-auto border border-slate-700" id="console">
      <div class="text-slate-500">// Console</div>
      <div class="text-slate-400">En attente d'une URL...</div>
    </div>
  </div>

  <!-- ══ FICHE VIDÉO ══ -->
  <div id="videoCard" class="hidden space-y-4">

    <!-- Thumbnail principale + méta -->
    <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 overflow-hidden">
      <div class="relative bg-black">
        <img id="vThumb" src="" alt="" class="w-full object-cover max-h-72" />
        <div id="vLive"   class="hidden absolute top-3 left-3 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded">LIVE</div>
        <div id="vSource" class="absolute top-3 right-3 text-white text-xs font-bold px-2 py-0.5 rounded"></div>
      </div>
      <div class="p-5 space-y-4">
        <div>
          <h2 id="vTitle" class="text-white font-bold text-xl leading-tight"></h2>
          <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm text-slate-400">
            <span id="vAuthor"></span>
            <span id="vViews"></span>
            <span id="vDuration"></span>
            <span id="vDate"></span>
          </div>
        </div>

        <div id="vDescBox" class="hidden">
          <div class="text-slate-500 text-xs uppercase tracking-widest mb-1">Description</div>
          <p id="vDesc" class="text-slate-300 text-sm line-clamp-3 leading-relaxed whitespace-pre-line"></p>
          <button onclick="toggleDesc()" id="vDescBtn" class="text-xs text-red-400 hover:text-red-300 mt-1 transition-colors">Voir plus</button>
        </div>

        <div id="vJsonRow" class="hidden flex items-center gap-2 text-xs">
          <i class="fas fa-file-code text-yellow-400"></i>
          <span id="vJsonName" class="text-slate-400 font-mono flex-1"></span>
          <a id="vJsonOpen" href="#" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded transition-all">Ouvrir</a>
          <a id="vJsonDl"   href="#" download         class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded transition-all">Télécharger</a>
        </div>
      </div>
    </div>

    <!-- ══ FORMATS PROGRESSIFS ══ -->
    <div id="fmtProgWrap" class="hidden bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
        <i class="fas fa-film text-green-400 text-sm"></i>
        <span class="text-slate-300 text-sm font-semibold">Formats progressifs</span>
        <span class="text-slate-500 text-xs">(vidéo + audio)</span>
        <span id="fmtProgCount" class="ml-auto bg-slate-700 text-slate-400 text-xs px-2 py-0.5 rounded-full"></span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-slate-500 border-b border-slate-700">
              <th class="text-left px-3 py-2 font-semibold w-20">Qualité</th>
              <th class="text-left px-3 py-2 font-semibold w-16">Format</th>
              <th class="text-left px-3 py-2 font-semibold">Codecs</th>
              <th class="text-left px-3 py-2 font-semibold w-14">FPS</th>
              <th class="text-left px-3 py-2 font-semibold w-20">Taille</th>
              <th class="text-right px-3 py-2 font-semibold w-36">Actions</th>
            </tr>
          </thead>
          <tbody id="fmtProg"></tbody>
        </table>
      </div>
    </div>

    <!-- ══ FORMATS VIDÉO SEULE ══ -->
    <div id="fmtVidWrap" class="hidden bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
        <i class="fas fa-video text-blue-400 text-sm"></i>
        <span class="text-slate-300 text-sm font-semibold">Vidéo seule</span>
        <span class="text-slate-500 text-xs">(sans audio)</span>
        <span id="fmtVidCount" class="ml-auto bg-slate-700 text-slate-400 text-xs px-2 py-0.5 rounded-full"></span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-slate-500 border-b border-slate-700">
              <th class="text-left px-3 py-2 font-semibold w-20">Qualité</th>
              <th class="text-left px-3 py-2 font-semibold w-16">Format</th>
              <th class="text-left px-3 py-2 font-semibold">Codec</th>
              <th class="text-left px-3 py-2 font-semibold w-14">FPS</th>
              <th class="text-left px-3 py-2 font-semibold w-20">Taille</th>
              <th class="text-right px-3 py-2 font-semibold w-36">Actions</th>
            </tr>
          </thead>
          <tbody id="fmtVid"></tbody>
        </table>
      </div>
    </div>

    <!-- ══ FORMATS AUDIO SEUL ══ -->
    <div id="fmtAudWrap" class="hidden bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
        <i class="fas fa-music text-purple-400 text-sm"></i>
        <span class="text-slate-300 text-sm font-semibold">Audio seul</span>
        <span id="fmtAudCount" class="ml-auto bg-slate-700 text-slate-400 text-xs px-2 py-0.5 rounded-full"></span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-slate-500 border-b border-slate-700">
              <th class="text-left px-3 py-2 font-semibold w-20">Qualité</th>
              <th class="text-left px-3 py-2 font-semibold w-16">Format</th>
              <th class="text-left px-3 py-2 font-semibold">Codec</th>
              <th class="text-left px-3 py-2 font-semibold w-20">Kbps</th>
              <th class="text-left px-3 py-2 font-semibold w-20">Taille</th>
              <th class="text-right px-3 py-2 font-semibold w-36">Actions</th>
            </tr>
          </thead>
          <tbody id="fmtAud"></tbody>
        </table>
      </div>
    </div>

    <!-- ══ THUMBNAILS / IMAGES ══ -->
    <div id="thumbsWrap" class="hidden bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
        <i class="fas fa-images text-amber-400 text-sm"></i>
        <span class="text-slate-300 text-sm font-semibold">Toutes les images</span>
        <span id="thumbsCount" class="ml-auto bg-slate-700 text-slate-400 text-xs px-2 py-0.5 rounded-full"></span>
      </div>
      <div id="thumbsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-4"></div>
    </div>

    <!-- ══ STORYBOARDS ══ -->
    <div id="sbWrap" class="hidden bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
        <i class="fas fa-film text-cyan-400 text-sm"></i>
        <span class="text-slate-300 text-sm font-semibold">Storyboards</span>
        <span class="text-slate-500 text-xs">(hover timeline du lecteur)</span>
        <span id="sbCount" class="ml-auto bg-slate-700 text-slate-400 text-xs px-2 py-0.5 rounded-full"></span>
      </div>
      <!-- Onglets niveaux -->
      <div id="sbTabs" class="flex gap-1 px-4 pt-3 pb-0 overflow-x-auto"></div>
      <!-- Contenu -->
      <div id="sbContent" class="p-4 space-y-3"></div>
    </div>

  </div><!-- /videoCard -->

</div><!-- /max-w -->

<script>
const $ = id => document.getElementById(id);
const consoleEl = $('console');
let currentTitle = 'video';
let descExpanded = false;

// ── Utilitaires ──────────────────────────────────────────────
function log(msg, type = 'info') {
  const div = document.createElement('div');
  const t = new Date().toLocaleTimeString('fr-FR');
  div.className = type === 'error' ? 'text-red-400' : type === 'success' ? 'text-green-400' : 'text-blue-400';
  div.innerHTML = `<span class="text-slate-600">[${t}]</span> ${msg}`;
  consoleEl.appendChild(div);
  consoleEl.scrollTop = consoleEl.scrollHeight;
}
function setProgress(pct, label = '') {
  $('progressBar').style.width = pct + '%';
  $('progressText').textContent = pct + '%';
  if (label) $('progressLabel').textContent = label;
}
const pad       = n => String(n).padStart(2, '0');
const fmtViews  = n => n ? parseInt(n).toLocaleString('fr-FR') + ' vues' : '—';
function fmtDuration(sec) {
  if (!sec) return '—';
  const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
  return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
}
function fmtSize(bytes) {
  if (!bytes) return '';
  const b = parseInt(bytes);
  return b > 1048576 ? (b/1048576).toFixed(1)+' Mo' : (b/1024).toFixed(0)+' Ko';
}
function safeFn(title, quality, ext) {
  return (title + '_' + quality + '.' + (ext || 'mp4'))
    .replace(/[^A-Za-z0-9_\-\.]/g, '_').replace(/_+/g, '_');
}
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '✓';
    btn.classList.add('bg-green-700');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('bg-green-700'); }, 1500);
  });
}

// ── Scrape ───────────────────────────────────────────────────
async function scrape() {
  const url = $('urlInput').value.trim();
  if (!url) { log('⚠️ URL manquante', 'error'); return; }

  $('videoCard').classList.add('hidden');
  $('progress').classList.remove('hidden');
  consoleEl.innerHTML = '<div class="text-slate-500">// Console</div>';
  const btn = $('btnScrape');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Chargement...';
  setProgress(10, 'Lancement yt-dlp...');
  log(`🚀 ${url}`);

  try {
    const fd = new FormData();
    fd.append('url', url);

    setProgress(25, 'yt-dlp en cours...');
    log('🔧 yt-dlp -J ...');

    const res  = await fetch('api.php', { method: 'POST', body: fd });
    setProgress(80, 'Parsing...');
    const data = await res.json();

    if (!data.success) throw new Error(data.error || 'Erreur inconnue');

    // Logs
    if (data.ytdlp_error) log('⚠️ yt-dlp: ' + data.ytdlp_error.substring(0, 120));
    if (data.clients_tried?.length) data.clients_tried.forEach(c => log('🔑 ' + c));

    const srcLabels = { ytdlp: '✅ yt-dlp', api: '✅ API ' + (data.client||''), html: '⚠️ HTML fallback' };
    log(srcLabels[data.source] || data.source, data.source === 'html' ? 'info' : 'success');

    const d = data.yt_data;
    log(`🎬 "${d.title}"`, 'success');
    log(`📦 ${d.formats?.length||0} progressifs · ${(d.adaptive||[]).filter(f=>f.hasVideo&&!f.hasAudio).length} vidéo · ${(d.adaptive||[]).filter(f=>f.hasAudio&&!f.hasVideo).length} audio · ${d.thumbnails?.length||0} images`, 'success');

    setProgress(100, 'Terminé');
    renderVideo(data);

  } catch (err) {
    setProgress(0, 'Erreur');
    log(`❌ ${err.message}`, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-search mr-2"></i>Extraire';
  }
}

// ── Rendu vidéo ──────────────────────────────────────────────
function renderVideo(data) {
  const d = data.yt_data;
  currentTitle = d.title || 'video';

  // Hero thumb
  if (d.thumbnail) { $('vThumb').src = d.thumbnail; $('vThumb').style.display = ''; }
  else             { $('vThumb').style.display = 'none'; }
  d.isLive ? $('vLive').classList.remove('hidden') : $('vLive').classList.add('hidden');

  const srcEl = $('vSource');
  const srcMap = { ytdlp: ['yt-dlp','source-ytdlp'], api: [data.client||'API','source-api'], html: ['HTML','source-html'] };
  const [srcTxt, srcCls] = srcMap[data.source] || ['?','source-html'];
  srcEl.textContent = srcTxt;
  srcEl.className   = 'absolute top-3 right-3 text-white text-xs font-bold px-2 py-0.5 rounded ' + srcCls;

  $('vTitle').textContent   = d.title  || '—';
  $('vAuthor').innerHTML    = `<i class="fas fa-user mr-1 text-slate-500"></i>${d.author || '—'}`;
  $('vViews').innerHTML     = `<i class="fas fa-eye mr-1 text-slate-500"></i>${fmtViews(d.viewCount)}`;
  $('vDuration').innerHTML  = `<i class="fas fa-clock mr-1 text-slate-500"></i>${fmtDuration(d.lengthSec)}`;
  $('vDate').innerHTML      = `<i class="fas fa-calendar mr-1 text-slate-500"></i>${d.publishDate || '—'}`;

  if (d.description) {
    $('vDesc').textContent = d.description;
    $('vDescBox').classList.remove('hidden');
    $('vDesc').classList.add('line-clamp-3');
    $('vDescBtn').textContent = 'Voir plus';
    descExpanded = false;
  } else {
    $('vDescBox').classList.add('hidden');
  }

  if (data.json_file) {
    $('vJsonName').textContent = data.json_file;
    $('vJsonOpen').href = data.json_file;
    $('vJsonDl').href   = data.json_file;
    $('vJsonRow').classList.remove('hidden');
  }

  // Formats
  renderFmtTable('fmtProg', 'fmtProgWrap', 'fmtProgCount',
    d.formats || [], 'progressive');
  renderFmtTable('fmtVid',  'fmtVidWrap',  'fmtVidCount',
    (d.adaptive||[]).filter(f => f.hasVideo && !f.hasAudio), 'video');
  renderFmtTable('fmtAud',  'fmtAudWrap',  'fmtAudCount',
    (d.adaptive||[]).filter(f => f.hasAudio && !f.hasVideo), 'audio');

  // Thumbnails
  renderThumbs(d.thumbnails || []);

  // Storyboards
  renderStoryboards(d.storyboards || []);

  $('videoCard').classList.remove('hidden');
}

// ── Table de formats ─────────────────────────────────────────
function renderFmtTable(tbodyId, wrapId, countId, formats, mode) {
  const tbody = $(tbodyId);
  tbody.innerHTML = '';
  if (!formats.length) { $(wrapId).classList.add('hidden'); return; }
  $(wrapId).classList.remove('hidden');
  $(countId).textContent = formats.length;

  formats.forEach(f => {
    const quality = f.quality
      || (f.height ? f.height+'p' : (f.audioQ?.replace('AUDIO_QUALITY_','').toLowerCase() || '—'));
    const bitCol = mode === 'audio'
      ? (f.bitrate ? Math.round(f.bitrate/1000)+' kbps' : '—')
      : (f.fps ? f.fps+' fps' : '—');
    const fn = safeFn(currentTitle, quality, f.container);
    const dlUrl = f.url
      ? 'api.php?dl=1&fn=' + encodeURIComponent(fn) + '&url=' + encodeURIComponent(f.url)
      : null;

    const tr = document.createElement('tr');
    tr.className = 'frow border-t border-slate-800';
    tr.innerHTML = `
      <td class="px-3 py-2 font-bold text-white whitespace-nowrap">${quality}</td>
      <td class="px-3 py-2 text-slate-400">${f.container || '—'}</td>
      <td class="px-3 py-2 text-slate-500 max-w-[180px] truncate" title="${f.codecs||''}">${f.codecs || '—'}</td>
      <td class="px-3 py-2 text-slate-500">${bitCol}</td>
      <td class="px-3 py-2 text-slate-500 whitespace-nowrap">${fmtSize(f.filesize)}</td>
      <td class="px-3 py-2 text-right">
        ${f.url ? `
        <div class="flex justify-end gap-1">
          <button onclick="copyText('${f.url.replace(/'/g,"\\'")}', this)"
            class="bg-slate-700 hover:bg-slate-600 text-white px-2 py-1 rounded transition-all" title="Copier URL">
            <i class="fas fa-copy"></i></button>
          <a href="${dlUrl}" title="Télécharger"
            class="bg-blue-700 hover:bg-blue-600 text-white px-2 py-1 rounded transition-all whitespace-nowrap">
            <i class="fas fa-download"></i></a>
          <a href="${f.url}" target="_blank" title="Ouvrir dans un nouvel onglet"
            class="bg-slate-700 hover:bg-slate-600 text-white px-2 py-1 rounded transition-all">
            <i class="fas fa-external-link-alt"></i></a>
        </div>` : '<span class="text-amber-600/70">chiffrée</span>'}
      </td>`;
    tbody.appendChild(tr);
  });
}

// ── Grille des thumbnails ────────────────────────────────────
function renderThumbs(thumbs) {
  const grid = $('thumbsGrid');
  grid.innerHTML = '';
  if (!thumbs.length) { $('thumbsWrap').classList.add('hidden'); return; }
  $('thumbsWrap').classList.remove('hidden');
  $('thumbsCount').textContent = thumbs.length + ' images';

  thumbs.forEach(t => {
    const ext  = t.url.split('.').pop().split('?')[0].toUpperCase();
    const dims = (t.width && t.height) ? `${t.width}×${t.height}` : (t.label || '');
    const fn   = safeFn(currentTitle + '_thumb', dims || ext, ext.toLowerCase() || 'jpg');

    const card = document.createElement('div');
    card.className = 'thumb-card bg-slate-900 rounded-xl overflow-hidden border border-slate-700 group';
    card.innerHTML = `
      <a href="${t.url}" target="_blank" class="block overflow-hidden bg-black">
        <img src="${t.url}" alt="${dims}" loading="lazy"
          class="w-full object-cover aspect-video"
          onerror="this.closest('.thumb-card').style.display='none'" />
      </a>
      <div class="px-2 py-1.5 flex items-center gap-1">
        <span class="text-slate-400 text-xs flex-1 truncate">${dims}</span>
        <span class="text-slate-600 text-xs font-mono">${ext}</span>
        <div class="flex gap-1 ml-1">
          <button onclick="copyText('${t.url.replace(/'/g,"\\'")}', this)"
            class="text-slate-500 hover:text-white transition-colors" title="Copier URL">
            <i class="fas fa-copy text-xs"></i></button>
          <a href="${t.url}" download="${fn}"
            class="text-slate-500 hover:text-blue-400 transition-colors" title="Télécharger">
            <i class="fas fa-download text-xs"></i></a>
        </div>
      </div>`;
    grid.appendChild(card);
  });
}

// ── Storyboards : frames splittées ───────────────────────────
function renderStoryboards(levels) {
  const wrap    = $('sbWrap');
  const tabs    = $('sbTabs');
  const content = $('sbContent');
  tabs.innerHTML = '';
  content.innerHTML = '';

  const valid = levels.filter(l => l.sheets && l.sheets.length);
  if (!valid.length) { wrap.classList.add('hidden'); return; }
  wrap.classList.remove('hidden');
  $('sbCount').textContent = valid.length + ' niveau' + (valid.length > 1 ? 'x' : '');

  let activeLevel = valid.length - 1;

  function fmtTime(sec) {
    sec = Math.floor(sec);
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${m}:${String(s).padStart(2,'0')}`;
  }

  function buildTab(l, idx) {
    const btn = document.createElement('button');
    const res = (l.width && l.height) ? `${l.width}×${l.height}` : (l.format_id || `L${idx}`);
    btn.id        = `sbTab${idx}`;
    btn.className = 'px-3 py-1.5 rounded-t text-xs font-semibold transition-all whitespace-nowrap '
                  + (idx === activeLevel
                      ? 'bg-slate-700 text-cyan-300 border border-b-0 border-slate-600'
                      : 'text-slate-500 hover:text-slate-300');
    btn.textContent = res;
    btn.onclick = () => { activeLevel = idx; renderLevel(); };
    return btn;
  }

  function updateTabs() {
    valid.forEach((_, i) => {
      const t = $('sbTab' + i);
      if (!t) return;
      t.className = t.className
        .replace('bg-slate-700 text-cyan-300 border border-b-0 border-slate-600', '')
        .replace('text-slate-500 hover:text-slate-300', '')
        .trim();
      t.className += i === activeLevel
        ? ' bg-slate-700 text-cyan-300 border border-b-0 border-slate-600'
        : ' text-slate-500 hover:text-slate-300';
    });
  }

  function renderLevel() {
    updateTabs();
    const l = valid[activeLevel];
    content.innerHTML = '';

    // ── Bandeau infos ──
    const intervalSec = l.interval > 0 ? l.interval / 1000 : null;
    const fps    = intervalSec ? (1 / intervalSec).toFixed(3) + ' fps' : '—';
    const source = l.source === 'ytdlp' ? 'yt-dlp' : 'spec';
    const info   = document.createElement('div');
    info.className = 'flex flex-wrap gap-2 text-xs text-slate-400 mb-4';
    info.innerHTML = [
      l.width  ? `<span class="bg-slate-700 px-2 py-0.5 rounded-full">${l.width}×${l.height} px/tile</span>` : '',
      `<span class="bg-slate-700 px-2 py-0.5 rounded-full">${l.cols}×${l.rows} tiles/sprite</span>`,
      fps !== '—' ? `<span class="bg-slate-700 px-2 py-0.5 rounded-full">${fps}</span>` : '',
      intervalSec ? `<span class="bg-slate-700 px-2 py-0.5 rounded-full">1 frame / ${intervalSec}s</span>` : '',
      l.count    ? `<span class="bg-slate-700 px-2 py-0.5 rounded-full">${l.count} frames</span>` : '',
      `<span class="bg-slate-700 px-2 py-0.5 rounded-full text-slate-500">${source}</span>`,
    ].filter(Boolean).join('');
    content.appendChild(info);

    // ── Grille de frames ──
    const DISP_W  = 160;
    const DISP_H  = (l.width && l.height) ? Math.round(DISP_W * l.height / l.width) : 90;
    const SPRITE_W = l.cols * DISP_W;
    const SPRITE_H = l.rows * DISP_H;

    // perFrameSec depuis un sheet plein (pas le dernier si plusieurs sheets)
    // sheet.duration / (cols × rows) = durée exacte d'une miniature
    const fullSheet   = l.sheets.length > 1 ? l.sheets[0] : l.sheets[0];
    const fullSheetDur = fullSheet && fullSheet.duration != null ? fullSheet.duration : null;
    const perFrameSec  = fullSheetDur ? fullSheetDur / (l.cols * l.rows)
                       : (intervalSec || null);

    const grid = document.createElement('div');
    grid.className = 'flex flex-wrap gap-2';

    const MAX_FRAMES = 400;
    let shown = 0;
    let runningTime = 0; // secondes cumulées depuis le début de la vidéo

    outer: for (let si = 0; si < l.sheets.length; si++) {
      const sheet = l.sheets[si];
      const url   = (typeof sheet === 'object') ? sheet.url  : sheet;
      const sheetDur = (typeof sheet === 'object' && sheet.duration != null)
                     ? sheet.duration : null;

      // Nombre exact de frames dans ce sheet : sheetDur / perFrameSec
      // (le dernier sheet a une durée plus courte → moins de miniatures)
      let framesInSheet;
      if (sheetDur != null && perFrameSec) {
        framesInSheet = Math.max(1, Math.round(sheetDur / perFrameSec));
      } else if (si === l.sheets.length - 1 && l.count) {
        framesInSheet = Math.max(1, l.count - si * l.cols * l.rows);
      } else {
        framesInSheet = l.cols * l.rows;
      }

      const sheetStartTime = runningTime;

      for (let fi = 0; fi < framesInSheet; fi++) {
        if (shown >= MAX_FRAMES) {
          // Calculer le total restant approximatif
          let remaining = 0;
          for (let ri = si; ri < l.sheets.length; ri++) {
            const rs = l.sheets[ri];
            const rd = (typeof rs === 'object' && rs.duration != null) ? rs.duration : null;
            remaining += rd && perFrameSec ? Math.round(rd / perFrameSec) : l.cols * l.rows;
          }
          remaining -= fi;
          const more = document.createElement('div');
          more.className = 'text-slate-500 text-xs self-center px-2 py-2';
          more.textContent = `+ ${remaining} frames…`;
          grid.appendChild(more);
          break outer;
        }

        const col     = fi % l.cols;
        const row     = Math.floor(fi / l.cols);
        const timeSec = perFrameSec != null ? sheetStartTime + fi * perFrameSec : null;

        const cell = document.createElement('div');
        cell.className = 'relative rounded overflow-hidden border border-slate-700 bg-black flex-shrink-0 group cursor-pointer';
        cell.style.width  = DISP_W + 'px';
        cell.style.height = DISP_H + 'px';
        cell.title = timeSec !== null ? fmtTime(timeSec) : `frame ${shown}`;

        const thumb = document.createElement('div');
        thumb.style.cssText =
          `width:${DISP_W}px;height:${DISP_H}px;` +
          `background-image:url('${url.replace(/'/g,"\\'")}');` +
          `background-size:${SPRITE_W}px ${SPRITE_H}px;` +
          `background-position:-${col * DISP_W}px -${row * DISP_H}px;` +
          `background-repeat:no-repeat;`;

        // Timestamp en haut à gauche (permanent)
        const ts = document.createElement('div');
        ts.className = 'absolute top-0 left-0 text-xs font-mono text-white/90 bg-black/60 px-1 leading-5';
        ts.textContent = timeSec !== null ? fmtTime(timeSec) : `#${shown}`;

        // Timestamp bas centré au hover
        const badge = document.createElement('div');
        badge.className =
          'absolute bottom-0 left-0 right-0 text-center text-xs font-mono text-white ' +
          'bg-black/75 py-0.5 opacity-0 group-hover:opacity-100 transition-opacity';
        badge.textContent = timeSec !== null ? fmtTime(timeSec) : `#${shown}`;

        cell.appendChild(thumb);
        cell.appendChild(ts);
        cell.appendChild(badge);
        grid.appendChild(cell);
        shown++;
      }

      // Avancer le temps cumulé pour le prochain sheet
      runningTime += sheetDur != null ? sheetDur
                   : (perFrameSec ? framesInSheet * perFrameSec : 0);
    }

    content.appendChild(grid);
  }

  valid.forEach((l, i) => tabs.appendChild(buildTab(l, i)));
  renderLevel();
}

// ── Description toggle ───────────────────────────────────────
function toggleDesc() {
  descExpanded = !descExpanded;
  $('vDesc').classList.toggle('line-clamp-3', !descExpanded);
  $('vDescBtn').textContent = descExpanded ? 'Voir moins' : 'Voir plus';
}

$('urlInput').addEventListener('keydown', e => { if (e.key === 'Enter') scrape(); });
</script>
</body>
</html>
