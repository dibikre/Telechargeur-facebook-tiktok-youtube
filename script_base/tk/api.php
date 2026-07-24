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

// Valide que c'est bien TikTok
if (!preg_match('#(tiktok\.com|vm\.tiktok\.com)#i', $url)) {
    echo json_encode(['success' => false, 'error' => 'URL TikTok invalide']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// yt-dlp extraction
// ══════════════════════════════════════════════════════════════════════════════

function runYtdlp(string $url): ?array
{
    $cmd    = 'yt-dlp --dump-json --no-playlist --no-warnings --no-check-certificate '
            . escapeshellarg($url) . ' 2>&1';
    $output = shell_exec($cmd);
    if (!$output) return null;

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

function buildFromYtdlp(array $d): array
{
    $videoId = $d['id'] ?? null;

    // ── Catégoriser les formats ───────────────────────────────────────────────
    $videoFormats = [];
    $audioFormats = [];
    $liveStreams   = [];

    foreach ($d['formats'] ?? [] as $f) {
        $fid    = $f['format_id']  ?? '';
        $vcodec = $f['vcodec']     ?? 'none';
        $acodec = $f['acodec']     ?? 'none';
        $fUrl   = $f['url']        ?? '';
        $ext    = $f['ext']        ?? 'mp4';
        $proto  = $f['protocol']   ?? '';
        if (!$fUrl) continue;

        // Live
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

        // Audio seul
        if ($vcodec === 'none' && $acodec !== 'none') {
            $audioFormats[] = [
                'id'     => $fid,
                'url'    => $fUrl,
                'ext'    => $ext,
                'acodec' => $acodec,
                'asr'    => $f['asr']  ?? null,
                'abr'    => isset($f['abr']) ? round($f['abr'], 1) : null,
                'tbr'    => isset($f['tbr']) ? round($f['tbr'], 1) : null,
                'filesize' => $f['filesize'] ?? $f['filesize_approx'] ?? null,
            ];
            continue;
        }

        // Vidéo (avec ou sans audio intégré)
        if ($vcodec !== 'none') {
            $hasAudio = ($acodec !== 'none');
            $note     = $f['format_note'] ?? '';
            $videoFormats[] = [
                'id'         => $fid,
                'url'        => $fUrl,
                'ext'        => $ext,
                'width'      => $f['width']  ?? null,
                'height'     => $f['height'] ?? null,
                'resolution' => $f['resolution'] ?? (($f['width'] ?? null) ? "{$f['width']}x{$f['height']}" : null),
                'vcodec'     => $vcodec,
                'acodec'     => $acodec,
                'has_audio'  => $hasAudio,
                'tbr'        => isset($f['tbr'])  ? round($f['tbr'],  1) : null,
                'vbr'        => isset($f['vbr'])  ? round($f['vbr'],  1) : null,
                'fps'        => $f['fps']    ?? null,
                'dynamic_range' => $f['dynamic_range'] ?? null,
                'note'       => $note,
                'filesize'   => $f['filesize'] ?? $f['filesize_approx'] ?? null,
                'watermarked'=> str_contains(strtolower($note), 'watermark'),
            ];
        }
    }

    // Tri vidéo : par hauteur desc, puis bitrate desc
    usort($videoFormats, function ($a, $b) {
        $hd = ($b['height'] ?? 0) <=> ($a['height'] ?? 0);
        return $hd !== 0 ? $hd : ($b['tbr'] ?? 0) <=> ($a['tbr'] ?? 0);
    });

    // ── Miniatures ────────────────────────────────────────────────────────────
    $seen   = [];
    $thumbs = [];
    if ($d['thumbnail'] ?? null) { $seen[$d['thumbnail']] = true; $thumbs[] = $d['thumbnail']; }
    foreach ($d['thumbnails'] ?? [] as $t) {
        $tu = $t['url'] ?? null;
        if ($tu && !isset($seen[$tu])) { $seen[$tu] = true; $thumbs[] = $tu; }
    }

    // ── Sous-titres / captions ────────────────────────────────────────────────
    $captions = [];
    $capSrc = array_merge(
        array_map(fn($k,$v)=>[$k,$v,'auto'],   array_keys($d['automatic_captions'] ?? []), array_values($d['automatic_captions'] ?? [])),
        array_map(fn($k,$v)=>[$k,$v,'manual'], array_keys($d['subtitles']          ?? []), array_values($d['subtitles']          ?? []))
    );
    foreach ($capSrc as [$lang, $tracks, $type]) {
        if (!is_array($tracks)) continue;
        foreach ($tracks as $t) {
            if ($t['url'] ?? null) {
                $captions[] = [
                    'lang' => $lang,
                    'type' => $type,
                    'ext'  => $t['ext']  ?? 'vtt',
                    'name' => $t['name'] ?? $lang,
                    'url'  => $t['url'],
                ];
            }
        }
    }

    // ── Sauvegarde JSON ───────────────────────────────────────────────────────
    $jsonSaved = null;
    if ($videoId) {
        $f = __DIR__ . "/{$videoId}.json";
        file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $jsonSaved = "{$videoId}.json";
    }

    // ── Date ──────────────────────────────────────────────────────────────────
    $uploadDate = null;
    if (isset($d['upload_date']) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $d['upload_date'], $dm)) {
        $uploadDate = "{$dm[3]}/{$dm[2]}/{$dm[1]}";
    }

    // ── is_live ───────────────────────────────────────────────────────────────
    $isLive = (bool)($d['is_live'] ?? false);
    if (!$isLive && ($d['concurrent_view_count'] ?? 0) > 0) $isLive = true;

    // ── Musique / sound ───────────────────────────────────────────────────────
    $music = null;
    if ($d['track'] ?? null) {
        $music = [
            'track'  => $d['track']  ?? null,
            'artist' => $d['artist'] ?? ($d['creator'] ?? null),
            'album'  => $d['album']  ?? null,
        ];
    }

    return [
        'video_id'              => $videoId,
        'title'                 => $d['title']       ?? ($d['description'] ?? 'Vidéo TikTok'),
        'description'           => $d['description'] ?? null,
        'uploader'              => $d['uploader']    ?? ($d['creator'] ?? null),
        'uploader_id'           => $d['uploader_id'] ?? null,
        'uploader_url'          => $d['uploader_url'] ?? (isset($d['uploader_id']) ? "https://www.tiktok.com/@{$d['uploader_id']}" : null),
        'thumbnail'             => $thumbs[0] ?? null,
        'thumbnails'            => $thumbs,
        'view_count'            => $d['view_count']    ?? null,
        'like_count'            => $d['like_count']    ?? null,
        'comment_count'         => $d['comment_count'] ?? null,
        'repost_count'          => $d['repost_count']  ?? null,
        'concurrent_view_count' => $d['concurrent_view_count'] ?? null,
        'is_live'               => $isLive,
        'duration_sec'          => $d['duration'] ?? null,
        'duration_string'       => $d['duration_string'] ?? null,
        'timestamp'             => $d['timestamp']   ?? null,
        'upload_date'           => $uploadDate,
        'music'                 => $music,
        'formats' => [
            'video'  => $videoFormats,
            'audio'  => $audioFormats,
            'live'   => $liveStreams,
        ],
        'captions'    => $captions,
        'json_file'   => $jsonSaved,
        'original_url'=> $d['original_url'] ?? $d['webpage_url'] ?? null,
        'webpage_url' => $d['webpage_url']  ?? null,
    ];
}

// ── Dispatch ──────────────────────────────────────────────────────────────────

$result = runYtdlp($url);

if ($result !== null) {
    $data = buildFromYtdlp($result);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => false,
    'error'   => 'Impossible d\'extraire la vidéo. Vérifiez que yt-dlp est installé et que l\'URL est publique.',
]);
