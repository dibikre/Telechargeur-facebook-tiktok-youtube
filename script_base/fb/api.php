<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

set_time_limit(120);
error_reporting(0);

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
$url  = trim($body['url'] ?? $_POST['url'] ?? '');

if (!$url) {
    echo json_encode(['success' => false, 'error' => 'URL manquante']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// MÉTHODE 1 — yt-dlp (prioritaire)
// ══════════════════════════════════════════════════════════════════════════════

function runYtdlp(string $url): ?array
{
    $cmd    = 'yt-dlp --dump-json --no-playlist --no-warnings --no-check-certificate '
            . escapeshellarg($url) . ' 2>&1';
    $output = shell_exec($cmd);
    if (!$output) return null;

    // Plusieurs lignes JSON possibles (playlist) : on prend la première valide
    foreach (explode("\n", trim($output)) as $line) {
        $line = trim($line);
        if (!$line || $line[0] !== '{') continue;
        $data = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['id'])) {
            return $data;
        }
    }
    return null;
}

function buildFromYtdlp(array $d, string $rawUrl): array
{
    $videoId = $d['id'] ?? null;

    // ── Formats ──────────────────────────────────────────────────────────────
    $progressive = [];
    $dashVideo   = [];
    $dashAudio   = [];
    $liveStreams  = [];

    foreach ($d['formats'] ?? [] as $f) {
        $fid    = $f['format_id'] ?? '';
        $vcodec = $f['vcodec'] ?? 'none';
        $acodec = $f['acodec'] ?? 'none';
        $fUrl   = $f['url'] ?? '';
        $ext    = $f['ext'] ?? 'mp4';
        $note   = $f['format_note'] ?? '';
        $proto  = $f['protocol'] ?? '';
        if (!$fUrl) continue;

        // Live HLS/DASH
        if (in_array($proto, ['m3u8', 'm3u8_native', 'dash']) && ($d['is_live'] ?? false)) {
            $liveStreams[] = [
                'id'         => $fid,
                'url'        => $fUrl,
                'ext'        => $ext,
                'protocol'   => $proto,
                'resolution' => $f['resolution'] ?? null,
                'tbr'        => $f['tbr'] ?? null,
                'vcodec'     => $vcodec !== 'none' ? $vcodec : null,
                'acodec'     => $acodec !== 'none' ? $acodec : null,
            ];
            continue;
        }

        // Progressive (sd / hd)
        if (in_array($fid, ['sd', 'hd'])) {
            $progressive[] = [
                'id'       => $fid,
                'label'    => strtoupper($fid),
                'url'      => $fUrl,
                'ext'      => $ext,
                'width'    => $f['width']  ?? null,
                'height'   => $f['height'] ?? null,
                'tbr'      => $f['tbr']    ?? null,
            ];
            continue;
        }

        // DASH vidéo
        if ($vcodec !== 'none' && $acodec === 'none' && str_contains($note, 'DASH')) {
            $dashVideo[] = [
                'id'         => $fid,
                'url'        => $fUrl,
                'ext'        => $ext,
                'width'      => $f['width']  ?? null,
                'height'     => $f['height'] ?? null,
                'resolution' => $f['resolution'] ?? (($f['width'] ?? null) ? "{$f['width']}x{$f['height']}" : null),
                'vcodec'     => $vcodec,
                'tbr'        => $f['tbr'] ?? null,
                'fps'        => $f['fps'] ?? null,
            ];
            continue;
        }

        // DASH audio
        if ($vcodec === 'none' && $acodec !== 'none' && str_contains($note, 'DASH')) {
            $dashAudio[] = [
                'id'     => $fid,
                'url'    => $fUrl,
                'ext'    => $ext,
                'acodec' => $acodec,
                'asr'    => $f['asr'] ?? null,
                'abr'    => isset($f['abr']) ? round($f['abr'], 1) : null,
            ];
        }
    }

    // Tri DASH vidéo par bitrate desc
    usort($dashVideo, fn($a, $b) => ($b['tbr'] ?? 0) <=> ($a['tbr'] ?? 0));
    // Progressive : hd avant sd
    usort($progressive, fn($a, $b) => ($a['id'] === 'hd' ? 0 : 1) <=> ($b['id'] === 'hd' ? 0 : 1));

    // ── Miniatures ────────────────────────────────────────────────────────────
    $thumbsSeen  = [];
    $thumbsArray = [];
    if ($d['thumbnail'] ?? null) {
        $thumbsSeen[$d['thumbnail']] = true;
        $thumbsArray[] = $d['thumbnail'];
    }
    foreach ($d['thumbnails'] ?? [] as $t) {
        $tu = $t['url'] ?? null;
        if ($tu && !isset($thumbsSeen[$tu])) {
            $thumbsSeen[$tu] = true;
            $thumbsArray[]   = $tu;
        }
    }

    // ── Sous-titres / captions ────────────────────────────────────────────────
    $captions = [];
    $capSources = array_merge(
        array_map(fn($k, $v) => [$k, $v, 'auto'],  array_keys($d['automatic_captions'] ?? []), array_values($d['automatic_captions'] ?? [])),
        array_map(fn($k, $v) => [$k, $v, 'manual'], array_keys($d['subtitles']          ?? []), array_values($d['subtitles']          ?? []))
    );
    foreach ($capSources as [$lang, $tracks, $type]) {
        if (!is_array($tracks)) continue;
        foreach ($tracks as $t) {
            if ($t['url'] ?? null) {
                $captions[] = [
                    'lang' => $lang,
                    'type' => $type,
                    'ext'  => $t['ext']  ?? 'srt',
                    'name' => $t['name'] ?? $lang,
                    'url'  => $t['url'],
                ];
            }
        }
    }

    // ── Sauvegarde JSON complet ───────────────────────────────────────────────
    $jsonSaved = null;
    if ($videoId) {
        $jsonFile = __DIR__ . "/{$videoId}.json";
        file_put_contents($jsonFile, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $jsonSaved = "{$videoId}.json";
    }

    // ── Date lisible ──────────────────────────────────────────────────────────
    $uploadDate = null;
    if (isset($d['upload_date']) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $d['upload_date'], $dm)) {
        $uploadDate = "{$dm[3]}/{$dm[2]}/{$dm[1]}";
    }

    // ── URL profil uploader ───────────────────────────────────────────────────
    $uploaderUrl = $d['uploader_url'] ?? null;
    if (!$uploaderUrl && ($d['uploader_id'] ?? null)) {
        $uid = $d['uploader_id'];
        $uploaderUrl = is_numeric($uid)
            ? "https://www.facebook.com/profile.php?id={$uid}"
            : "https://www.facebook.com/{$uid}";
    }

    // ── is_live ───────────────────────────────────────────────────────────────
    $isLive = (bool)($d['is_live'] ?? false);
    if (!$isLive && ($d['concurrent_view_count'] ?? 0) > 0) $isLive = true;

    return [
        'video_id'              => $videoId,
        'title'                 => $d['title'] ?? 'Vidéo Facebook',
        'description'           => $d['description'] ?? null,
        'uploader'              => $d['uploader'] ?? null,
        'uploader_id'           => $d['uploader_id'] ?? null,
        'uploader_url'          => $uploaderUrl,
        'thumbnail'             => $thumbsArray[0] ?? null,
        'thumbnails'            => $thumbsArray,
        'view_count'            => $d['view_count'] ?? null,
        'like_count'            => $d['like_count'] ?? null,
        'concurrent_view_count' => $d['concurrent_view_count'] ?? null,
        'is_live'               => $isLive,
        'duration_sec'          => $d['duration'] ?? null,
        'duration_string'       => $d['duration_string'] ?? null,
        'timestamp'             => $d['timestamp'] ?? null,
        'upload_date'           => $uploadDate,
        'formats' => [
            'progressive' => $progressive,
            'dash_video'  => $dashVideo,
            'dash_audio'  => $dashAudio,
            'live'        => $liveStreams,
        ],
        'captions'    => $captions,
        'json_file'   => $jsonSaved,
        'original_url'=> $d['original_url'] ?? $d['webpage_url'] ?? $rawUrl,
        'source'      => 'ytdlp',
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
// MÉTHODE 2 — Scraping HTML (fallback)
// ══════════════════════════════════════════════════════════════════════════════

function curlGet(string $url, string $cookieJar): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-us,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Sec-Fetch-Mode: navigate',
            'Connection: keep-alive',
        ],
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_COOKIEJAR      => $cookieJar,
    ]);
    $html     = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);
    return ['html' => $html ?: '', 'url' => $finalUrl, 'errno' => $errno, 'error' => $error];
}

function fbDecodeUrl(string $raw): string
{
    $s = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $raw);
    $s = str_replace('\\/', '/', $s);
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function findJsonField(string $html, string $key): ?string
{
    if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $html, $m)) {
        return fbDecodeUrl($m[1]);
    }
    return null;
}

function findJsonInt(string $html, string $key): ?int
{
    if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*(\d+)/', $html, $m)) {
        return (int)$m[1];
    }
    return null;
}

function isValidVideoUrl(?string $url): bool
{
    return $url !== null && (bool)preg_match('#^https?://[^"<>\s]{20,}#', $url);
}

function extractDataSjsRaw(string $html): ?array
{
    if (!preg_match_all('/<script[^>]+type=["\']application\/json["\'][^>]+data-sjs[^>]*>(.*?)<\/script>/si', $html, $matches, PREG_OFFSET_CAPTURE)) return null;
    foreach ($matches[1] as $item) {
        $raw = $item[0];
        if (!str_contains($raw, 'shareable_url')) continue;
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }
    return null;
}

function findVideoDataNode(mixed $node, int $depth = 0): ?array
{
    if ($depth > 30 || !is_array($node)) return null;
    if (isset($node['shareable_url']) && isset($node['video_owner'])) return $node;
    foreach ($node as $v) {
        $found = findVideoDataNode($v, $depth + 1);
        if ($found !== null) return $found;
    }
    return null;
}

function buildFromScrape(string $url, string $rawUrl): array
{
    // Normaliser l'URL
    if (preg_match('#facebook\.com/watch[/?].*[?&]v=(\d+)#', $url, $m) ||
        preg_match('#facebook\.com/video/embed[/?].*video_id=(\d+)#', $url, $m)) {
        $url = "https://www.facebook.com/watch/?v={$m[1]}&_rdr";
    } elseif (preg_match('#facebook\.com/(?:reel|video|reels)/(\d+)#', $url, $m)) {
        $url = "https://www.facebook.com/watch/?v={$m[1]}&_rdr";
    }

    $cookieJar = tempnam(sys_get_temp_dir(), 'fb_cookies_');
    $result    = curlGet($url, $cookieJar);
    @unlink($cookieJar);

    if ($result['errno'] !== 0 || strlen($result['html']) < 500) {
        return [];
    }

    $html     = $result['html'];
    $finalUrl = $result['url'];

    $videoId = null;
    if (preg_match('#(?:v=|/videos/|/reel/)(\d{10,})#', $finalUrl, $m)) $videoId = $m[1];

    $rawSjsJson = extractDataSjsRaw($html);
    $videoData  = $rawSjsJson ? findVideoDataNode($rawSjsJson) : null;

    $ownerName   = $videoData['video_owner']['name']                   ?? null;
    $ownerUrl    = $videoData['video_owner']['url']                    ?? null;
    $ownerAvatar = $videoData['video_owner']['displayPicture']['uri']  ?? null;
    $firstFrame  = $videoData['video']['first_frame_thumbnail']        ?? null;
    $watermark   = $videoData['playback_video']['thumbnailImage']['uri'] ?? null;
    $shareUrl    = $videoData['shareable_url']                         ?? null;

    $jsonSaved = null;
    if ($videoData && $videoId) {
        file_put_contents(__DIR__ . "/{$videoId}.json", json_encode($videoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $jsonSaved = "{$videoId}.json";
    }

    $hdUrl = null; $sdUrl = null;
    foreach (['browser_native_hd_url', 'playable_url_quality_hd'] as $k) {
        $c = findJsonField($html, $k);
        if (isValidVideoUrl($c)) { $hdUrl = $c; break; }
    }
    foreach (['browser_native_sd_url', 'playable_url'] as $k) {
        $c = findJsonField($html, $k);
        if (isValidVideoUrl($c) && $c !== $hdUrl) { $sdUrl = $c; break; }
    }
    if (!$hdUrl && !$sdUrl) {
        preg_match_all('#https://[a-z0-9\-]+\.fbcdn\.net/[^\s"\'<>]{20,}\.mp4[^\s"\'<>]*#', $html, $allMp4);
        $mp4s = array_unique($allMp4[0] ?? []);
        if ($mp4s) { $sdUrl = fbDecodeUrl(reset($mp4s)); if (count($mp4s) > 1) $hdUrl = fbDecodeUrl(end($mp4s)); }
    }

    $title = findJsonField($html, 'title');
    if (!$title && preg_match('#<title[^>]*>(.*?)</title>#si', $html, $m)) {
        $title = preg_replace('/\s*\|\s*Facebook.*$/i', '', html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8'));
    }

    $durationMs  = ($videoData['video']['playable_duration_in_ms'] ?? null) ?: findJsonInt($html, 'duration_in_ms');
    $durationSec = $durationMs ? intval($durationMs / 1000) : findJsonInt($html, 'duration');

    $thumbnail = findJsonField($html, 'preferred_thumbnail')
              ?: findJsonField($html, 'thumbnailImage')
              ?: (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $html, $m) ? html_entity_decode($m[1], ENT_QUOTES, 'UTF-8') : null);

    $thumbs = array_filter([$firstFrame, $watermark, $thumbnail], fn($u) => isValidVideoUrl($u));
    $thumbs = array_values(array_unique($thumbs));

    $progressive = [];
    if ($hdUrl) $progressive[] = ['id' => 'hd', 'label' => 'HD', 'url' => $hdUrl, 'ext' => 'mp4', 'width' => null, 'height' => null, 'tbr' => null];
    if ($sdUrl) $progressive[] = ['id' => 'sd', 'label' => 'SD', 'url' => $sdUrl, 'ext' => 'mp4', 'width' => null, 'height' => null, 'tbr' => null];

    return [
        'video_id'              => $videoId,
        'title'                 => $title ? trim($title) : 'Vidéo Facebook',
        'description'           => null,
        'uploader'              => $ownerName,
        'uploader_id'           => null,
        'uploader_url'          => $ownerUrl,
        'thumbnail'             => $thumbnail,
        'thumbnails'            => $thumbs,
        'view_count'            => null,
        'like_count'            => null,
        'concurrent_view_count' => null,
        'is_live'               => false,
        'duration_sec'          => $durationSec,
        'duration_string'       => $durationSec ? gmdate('H:i:s', $durationSec) : null,
        'timestamp'             => null,
        'upload_date'           => null,
        'formats' => [
            'progressive' => $progressive,
            'dash_video'  => [],
            'dash_audio'  => [],
            'live'        => [],
        ],
        'captions'    => [],
        'json_file'   => $jsonSaved,
        'original_url'=> $shareUrl ?? $finalUrl,
        'source'      => 'scrape',
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
// DISPATCH
// ══════════════════════════════════════════════════════════════════════════════

$ytdlpResult = runYtdlp($url);

if ($ytdlpResult !== null) {
    $data = buildFromYtdlp($ytdlpResult, $url);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Fallback scraping
$data = buildFromScrape($url, $url);

if (empty($data['formats']['progressive'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'Aucune URL vidéo trouvée. Essayez avec une URL publique ou vérifiez que yt-dlp est installé.',
    ]);
    exit;
}

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
