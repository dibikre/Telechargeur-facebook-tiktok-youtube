<?php
header('Access-Control-Allow-Origin: *');
error_reporting(0);

// ============================================================
// MODE DOWNLOAD PROXY  (?dl=1&url=...&fn=...)
// ============================================================
if (isset($_GET['dl']) && isset($_GET['url'])) {
    $rawUrl = $_GET['url'];
    $parsed = parse_url($rawUrl);
    $host   = $parsed['host'] ?? '';
    if (!preg_match('/googlevideo\.com$/i', $host)) {
        http_response_code(403); exit('URL non autorisée');
    }

    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_GET['fn'] ?? 'video');
    if (!preg_match('/\.(mp4|webm|m4a|mp3|ogg)$/i', $filename)) $filename .= '.mp4';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $rawUrl, CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    curl_exec($ch);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $contentType   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    header('Content-Type: ' . ($contentType ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    if ($contentLength > 0) header('Content-Length: ' . (int)$contentLength);
    header('X-Accel-Buffering: no');

    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL => $rawUrl, CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 3600, CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_WRITEFUNCTION => function($ch, $data) { echo $data; flush(); return strlen($data); },
    ]);
    curl_exec($ch2);
    curl_close($ch2);
    exit;
}

// ============================================================
// MODE AJAX  (POST action=fetch)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

try {
    $ytUrl = filter_var(trim($_POST['url'] ?? ''), FILTER_VALIDATE_URL);
    if (!$ytUrl) throw new \Exception('URL invalide ou manquante');

    $videoId = null;
    if (preg_match('#(?:v=|youtu\.be/|/shorts/|/live/)([A-Za-z0-9_\-]{11})#', $ytUrl, $m)) {
        $videoId = $m[1];
    }
    if (!$videoId) throw new \Exception('Impossible d\'extraire le videoId');

    // ── 1. yt-dlp (source principale) ───────────────────────────────────────
    $ytdlpData = null;
    $ytdlpErr  = '';
    $ytdlpCmd  = 'yt-dlp -J --no-warnings --no-playlist ' . escapeshellarg($ytUrl) . ' 2>&1';
    $ytdlpOut  = (string)(shell_exec($ytdlpCmd) ?: '');

    // yt-dlp peut écrire des warnings/erreurs avant le JSON — on cherche le premier {
    $jsonStart = strpos($ytdlpOut, '{');
    if ($jsonStart !== false) {
        $ytdlpData = json_decode(substr($ytdlpOut, $jsonStart), true);
        if (json_last_error() !== JSON_ERROR_NONE) $ytdlpData = null;
    }
    if (!$ytdlpData) $ytdlpErr = trim(substr($ytdlpOut, 0, 300));

    // ── 2. Fallback : page HTML + API Android ───────────────────────────────
    $cookieFile = __DIR__ . '/cookies.txt';
    $outputFile = __DIR__ . '/code.html';
    if (file_exists($cookieFile)) unlink($cookieFile);

    $watchUrl = 'https://www.youtube.com/watch?v=' . $videoId . '&bpctr=9999999999&has_verified=1';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $watchUrl, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_ENCODING => '', CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.5 Safari/605.1.15,gzip(gfe)',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-us,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'Sec-Fetch-Mode: navigate',
            'Cookie: PREF=hl=en&tz=UTC; SOCS=CAI',
        ],
    ]);
    $html = (string)(curl_exec($ch) ?: '');
    curl_close($ch);

    if ($html !== '') file_put_contents($outputFile, $html, LOCK_EX);

    $visitorData  = extractStr($html, '"visitorData":"', '"') ?: extractStr($html, 'VISITOR_DATA":"', '"');
    $innertubeKey = extractStr($html, '"INNERTUBE_API_KEY":"', '"') ?: extractStr($html, '"innertubeApiKey":"', '"');

    // API YouTubei clients
    $apiData      = null;
    $usedClient   = null;
    $clientsLog   = [];

    if (!$ytdlpData) { // seulement si yt-dlp a échoué
        $clients = [
            ['name'=>'ANDROID',    'ver'=>'19.09.37','id'=>'3',
             'ua' =>'com.google.android.youtube/19.09.37 (Linux; U; Android 13; Pixel 7) gzip',
             'ctx'=>['clientName'=>'ANDROID','clientVersion'=>'19.09.37','androidSdkVersion'=>33,
                     'userAgent'=>'com.google.android.youtube/19.09.37 (Linux; U; Android 13; Pixel 7) gzip',
                     'osName'=>'Android','osVersion'=>'13','hl'=>'en','timeZone'=>'UTC','utcOffsetMinutes'=>0]],
            ['name'=>'ANDROID_VR', 'ver'=>'1.65.10', 'id'=>'28',
             'ua' =>'com.google.android.apps.youtube.vr.oculus/1.65.10 (Linux; U; Android 12L; eureka-user Build/SQ3A.220605.009.A1) gzip',
             'ctx'=>['clientName'=>'ANDROID_VR','clientVersion'=>'1.65.10','deviceMake'=>'Oculus','deviceModel'=>'Quest 3',
                     'androidSdkVersion'=>32,'userAgent'=>'com.google.android.apps.youtube.vr.oculus/1.65.10 (Linux; U; Android 12L; eureka-user Build/SQ3A.220605.009.A1) gzip',
                     'osName'=>'Android','osVersion'=>'12L','hl'=>'en','timeZone'=>'UTC','utcOffsetMinutes'=>0]],
            ['name'=>'IOS',        'ver'=>'19.09.3', 'id'=>'5',
             'ua' =>'com.google.ios.youtube/19.09.3 (iPhone16,2; U; CPU iOS 17_4_1 like Mac OS X;)',
             'ctx'=>['clientName'=>'IOS','clientVersion'=>'19.09.3','deviceMake'=>'Apple','deviceModel'=>'iPhone16,2',
                     'userAgent'=>'com.google.ios.youtube/19.09.3 (iPhone16,2; U; CPU iOS 17_4_1 like Mac OS X;)',
                     'osName'=>'iOS','osVersion'=>'17.4.1','hl'=>'en','timeZone'=>'UTC','utcOffsetMinutes'=>0]],
        ];

        foreach ($clients as $client) {
            $ctx = $client['ctx'];
            if ($visitorData) $ctx['visitorData'] = $visitorData;

            $payload = json_encode([
                'context' => ['client' => $ctx],
                'videoId' => $videoId,
                'playbackContext' => ['contentPlaybackContext' => ['html5Preference' => 'HTML5_PREF_WANTS']],
                'contentCheckOk' => true, 'racyCheckOk' => true,
            ]);
            $apiUrl  = 'https://www.youtube.com/youtubei/v1/player?prettyPrint=false' . ($innertubeKey ? '&key='.$innertubeKey : '');
            $headers = ['Content-Type: application/json',
                        'X-Youtube-Client-Name: '.$client['id'],
                        'X-Youtube-Client-Version: '.$client['ver'],
                        'Origin: https://www.youtube.com',
                        'Referer: https://www.youtube.com/watch?v='.$videoId];
            if ($visitorData) $headers[] = 'X-Goog-Visitor-Id: '.$visitorData;

            $raw  = curlPost($apiUrl, $payload, $client['ua'], $headers, $cookieFile);
            $dec  = $raw ? json_decode($raw, true) : null;
            $stat = $dec['playabilityStatus']['status'] ?? '';

            if ($dec && !in_array($stat, ['ERROR','LOGIN_REQUIRED','UNPLAYABLE']) && !empty($dec['streamingData'])) {
                $apiData    = $dec;
                $usedClient = $client['name'];
                $clientsLog[] = $client['name'] . ': ✓ OK';
                break;
            }
            $clientsLog[] = $client['name'] . ': ' . ($dec['playabilityStatus']['reason'] ?? ($stat ?: 'échec'));
        }
    }

    // ── 3. Fusionner les données ─────────────────────────────────────────────
    // Priorité source :
    //   formats  → yt-dlp (URLs directes fiables) > API Android > HTML
    //   metadata → yt-dlp > API Android > HTML

    $source  = 'html';
    $baseApi = null; // données player API (pour metadata)

    if ($ytdlpData) {
        $source  = 'ytdlp';
        $baseApi = extractYtInitialPlayerResponse($html); // pour metadata si absent de yt-dlp
    } elseif ($apiData) {
        $source  = 'api';
        $baseApi = $apiData;
    } else {
        $baseApi = extractYtInitialPlayerResponse($html);
    }

    if (!$ytdlpData && !$baseApi) throw new \Exception('Impossible de récupérer les données vidéo');

    // ── 4. Extraction metadata ───────────────────────────────────────────────
    if ($ytdlpData) {
        $title       = $ytdlpData['title']        ?? null;
        $author      = $ytdlpData['uploader']     ?? ($ytdlpData['channel'] ?? null);
        $channelUrl  = $ytdlpData['channel_url']  ?? ($ytdlpData['uploader_url'] ?? null);
        $lengthSec   = (int)($ytdlpData['duration'] ?? 0);
        $viewCount   = $ytdlpData['view_count']   ?? null;
        $description = $ytdlpData['description']  ?? null;
        $publishDate = $ytdlpData['upload_date']  ?? null;
        // Format YYYYMMDD → YYYY-MM-DD
        if ($publishDate && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $publishDate, $pd)) {
            $publishDate = "{$pd[1]}-{$pd[2]}-{$pd[3]}";
        }
        $isLive    = (bool)($ytdlpData['is_live']    ?? false);
        $isPrivate = (bool)($ytdlpData['availability'] === 'private');
        // Thumbnail principale
        $thumbMain = $ytdlpData['thumbnail'] ?? null;
    } else {
        $vd  = $baseApi['videoDetails'] ?? [];
        $mf  = $baseApi['microformat']['playerMicroformatRenderer'] ?? [];
        $thr = $vd['thumbnail']['thumbnails'] ?? [];
        usort($thr, fn($a,$b)=>(($b['width']??0)-($a['width']??0)));
        $title       = $vd['title']            ?? null;
        $author      = $vd['author']           ?? null;
        $channelUrl  = $mf['ownerProfileUrl']  ?? null;
        $lengthSec   = (int)($vd['lengthSeconds'] ?? 0);
        $viewCount   = $vd['viewCount']        ?? null;
        $description = $vd['shortDescription'] ?? null;
        $publishDate = $mf['publishDate']      ?? ($mf['uploadDate'] ?? null);
        $isLive      = (bool)($vd['isLiveContent'] ?? false);
        $isPrivate   = (bool)($vd['isPrivate']     ?? false);
        $thumbMain   = $thr[0]['url']          ?? null;
    }

    // ── 5. Formats ───────────────────────────────────────────────────────────
    if ($ytdlpData) {
        [$formats, $adaptive] = parseYtdlpFormats($ytdlpData['formats'] ?? []);
    } else {
        $sd       = $baseApi['streamingData'] ?? [];
        $formats  = parseApiFormats($sd['formats']        ?? [], 'progressive');
        $adaptive = parseApiFormats($sd['adaptiveFormats'] ?? [], 'adaptive');
    }

    // ── 6. Thumbnails (toutes les images disponibles) ────────────────────────
    $thumbnails = collectThumbnails($videoId, $ytdlpData);

    // ── 7. Storyboards (sprite sheets de la timeline) ────────────────────────
    $storyboards = collectStoryboards($ytdlpData, $baseApi);

    // ── 8. Sauvegarde JSON ───────────────────────────────────────────────────
    $saveData = $ytdlpData ?: $baseApi;
    $jsonFile = __DIR__ . "/{$videoId}.json";
    file_put_contents($jsonFile, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $response = [
        'success'      => true,
        'video_id'     => $videoId,
        'json_file'    => "{$videoId}.json",
        'source'       => $source,
        'client'       => $usedClient,
        'clients_tried'=> $clientsLog,
        'ytdlp_error'  => $ytdlpErr ?: null,
        'yt_data'      => [
            'videoId'     => $videoId,
            'title'       => $title,
            'author'      => $author,
            'channelUrl'  => $channelUrl,
            'lengthSec'   => $lengthSec,
            'viewCount'   => $viewCount,
            'description' => $description,
            'publishDate' => $publishDate,
            'thumbnail'   => $thumbMain,
            'isLive'      => $isLive,
            'isPrivate'   => $isPrivate,
            'formats'     => $formats,
            'adaptive'    => $adaptive,
            'thumbnails'  => $thumbnails,
            'storyboards' => $storyboards,
        ],
    ];

} catch (\Throwable $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;

// ============================================================
// HELPERS
// ============================================================

function curlPost(string $url, string $body, string $ua, array $headers, ?string $cookieFile = null): ?string
{
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_ENCODING => '', CURLOPT_USERAGENT => $ua, CURLOPT_HTTPHEADER => $headers,
    ];
    if ($cookieFile) $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    curl_setopt_array($ch, $opts);
    $out = curl_exec($ch);
    curl_close($ch);
    return $out ?: null;
}

function extractStr(string $str, string $before, string $after): ?string
{
    $pos = strpos($str, $before);
    if ($pos === false) return null;
    $s = $pos + strlen($before);
    $e = strpos($str, $after, $s);
    if ($e === false) return null;
    $v = substr($str, $s, $e - $s);
    return strlen($v) < 300 ? $v : null;
}

function extractYtInitialPlayerResponse(string $html): ?array
{
    $pos = strpos($html, 'ytInitialPlayerResponse');
    if ($pos === false) return null;
    $start = strpos($html, '{', $pos);
    if ($start === false) return null;
    $depth = 0; $inStr = false; $escape = false; $len = strlen($html);
    for ($i = $start; $i < $len; $i++) {
        $c = $html[$i];
        if ($escape)               { $escape = false; continue; }
        if ($c === '\\' && $inStr) { $escape = true;  continue; }
        if ($c === '"')            { $inStr = !$inStr; continue; }
        if ($inStr)                  continue;
        if ($c === '{')              $depth++;
        elseif ($c === '}') { $depth--; if ($depth === 0) {
            $d = json_decode(substr($html, $start, $i - $start + 1), true);
            return json_last_error() === JSON_ERROR_NONE ? $d : null;
        }}
    }
    return null;
}

// Formats depuis yt-dlp -J
function parseYtdlpFormats(array $formats): array
{
    $prog = []; $vid = []; $aud = [];
    foreach ($formats as $f) {
        $vcodec = $f['vcodec'] ?? 'none';
        $acodec = $f['acodec'] ?? 'none';
        $hasV   = ($vcodec !== 'none' && $vcodec !== null);
        $hasA   = ($acodec !== 'none' && $acodec !== null);
        if (!$hasV && !$hasA) continue;

        $row = [
            'itag'      => $f['format_id']    ?? null,
            'container' => $f['ext']          ?? null,
            'codecs'    => trim(($hasV ? $vcodec : '') . ($hasV && $hasA ? ', ' : '') . ($hasA ? $acodec : '')),
            'hasVideo'  => $hasV,
            'hasAudio'  => $hasA,
            'quality'   => $f['format_note']  ?? ($f['height'] ? $f['height'].'p' : null),
            'audioQ'    => null,
            'width'     => $f['width']        ?? null,
            'height'    => $f['height']       ?? null,
            'fps'       => isset($f['fps'])   ? (int)$f['fps'] : null,
            'bitrate'   => isset($f['tbr'])   ? (int)($f['tbr'] * 1000) : null,
            'asr'       => $f['asr']          ?? null,
            'channels'  => $f['audio_channels'] ?? null,
            'filesize'  => $f['filesize']     ?? ($f['filesize_approx'] ?? null),
            'url'       => $f['url']          ?? null,
        ];
        if ($hasV && $hasA) $prog[] = $row;
        elseif ($hasV)      $vid[]  = $row;
        else                $aud[]  = $row;
    }
    usort($prog, fn($a,$b)=>(($b['height']??0)-($a['height']??0)));
    usort($vid,  fn($a,$b)=>(($b['height']??0)-($a['height']??0)));
    return [$prog, array_merge($vid, $aud)];
}

// Formats depuis l'API YouTubei
function parseApiFormats(array $formats, string $type): array
{
    $out = [];
    foreach ($formats as $f) {
        $mime  = $f['mimeType'] ?? '';
        $base  = preg_match('#^([^;]+)#', $mime, $mm) ? trim($mm[1]) : $mime;
        $codec = preg_match('#codecs="([^"]+)"#', $mime, $cm) ? $cm[1] : '';
        [$mt, $ct] = array_pad(explode('/', $base), 2, '');
        $hasV = str_starts_with($mt, 'video');
        $hasA = isset($f['audioQuality']) || str_starts_with($mt, 'audio');
        $out[] = [
            'itag'      => $f['itag']            ?? null,
            'container' => $ct,
            'codecs'    => trim($codec),
            'hasVideo'  => $hasV,
            'hasAudio'  => $hasA,
            'quality'   => $f['qualityLabel']    ?? null,
            'audioQ'    => $f['audioQuality']    ?? null,
            'width'     => $f['width']           ?? null,
            'height'    => $f['height']          ?? null,
            'fps'       => $f['fps']             ?? null,
            'bitrate'   => $f['bitrate']         ?? null,
            'asr'       => $f['audioSampleRate'] ?? null,
            'channels'  => $f['audioChannels']   ?? null,
            'filesize'  => $f['contentLength']   ?? null,
            'url'       => $f['url']             ?? null,
        ];
    }
    usort($out, fn($a,$b)=>(($b['height']??0)-($a['height']??0)));
    return $out;
}

// ── Storyboards (sprite sheets de la barre de progression) ───────────────────
function collectStoryboards(?array $ytdlpData, ?array $baseApi): array
{
    $levels = [];

    // Source 1 : yt-dlp → formats mhtml avec fragments[]
    if ($ytdlpData) {
        $sb = [];
        foreach ($ytdlpData['formats'] ?? [] as $f) {
            if (($f['ext'] ?? '') !== 'mhtml') continue;
            $frags = $f['fragments'] ?? [];
            if (!$frags) continue;
            // Stocker url + duration par fragment (duration = secondes couvertes par le sprite sheet)
            $sheets = [];
            foreach ($frags as $fr) {
                $url = $fr['url'] ?? null;
                if (!$url) continue;
                $sheets[] = [
                    'url'      => $url,
                    'duration' => isset($fr['duration']) ? (float)$fr['duration'] : null,
                ];
            }
            if (!$sheets) continue;
            $cols = isset($f['columns']) ? (int)$f['columns'] : 3;
            $rows = isset($f['rows'])    ? (int)$f['rows']    : 3;
            $sb[] = [
                'level'    => count($sb),
                'format_id'=> $f['format_id'] ?? null,
                'width'    => isset($f['width'])  ? (int)$f['width']  : null,
                'height'   => isset($f['height']) ? (int)$f['height'] : null,
                'cols'     => $cols,
                'rows'     => $rows,
                'interval' => isset($f['fps']) && $f['fps'] > 0 ? (int)(1000 / $f['fps']) : 0,
                'count'    => null, // calculé côté JS depuis les durées
                'sheets'   => $sheets,
                'source'   => 'ytdlp',
            ];
        }
        // Trier par résolution croissante (sb0=basse res → sb3=haute res)
        usort($sb, fn($a, $b) => ($a['width'] ?? 0) <=> ($b['width'] ?? 0));
        if ($sb) return $sb;
    }

    // Source 2 : playerStoryboardSpecRenderer.spec dans le player JSON
    $spec = null;
    if ($baseApi) {
        $spec = $baseApi['storyboards']['playerStoryboardSpecRenderer']['spec']
             ?? $baseApi['storyboards']['playerLiveStoryboardSpecRenderer']['spec']
             ?? null;
    }
    if (!$spec) return [];

    return parseStoryboardSpec($spec);
}

// Parse le format spec YouTube :
// URL_TEMPLATE|W#H#COUNT#COLS#ROWS#INTERVAL#NAMEPAT#SIG|...
function parseStoryboardSpec(string $spec): array
{
    $parts   = explode('|', $spec);
    $urlBase = array_shift($parts);
    $levels  = [];

    foreach ($parts as $idx => $data) {
        $f = explode('#', $data);
        if (count($f) < 8) continue;

        [$w, $h, $count, $cols, $rows, $interval, $namePat, $sig] = $f;
        $w        = (int)$w;
        $h        = (int)$h;
        $count    = (int)$count;
        $cols     = max(1, (int)$cols);
        $rows     = max(1, (int)$rows);
        $interval = (int)$interval;

        $framesPerSheet = $cols * $rows;
        $numSheets      = (int)ceil($count / $framesPerSheet) ?: 1;
        $perFrameSec    = $interval > 0 ? $interval / 1000.0 : 0.0;
        $fullSheetDur   = $perFrameSec * $framesPerSheet;

        $sheets = [];
        for ($seg = 0; $seg < $numSheets; $seg++) {
            // namePat = "default" (niveau 0, un seul fichier) ou "M$M" (ex: M0, M1...)
            $segName  = str_replace('$M', (string)$seg, $namePat);
            $sheetUrl = str_replace(['$L', '$N'], [(string)$idx, $segName], $urlBase);
            // Ajouter la signature
            $sheetUrl .= (strpos($sheetUrl, '?') !== false ? '&' : '?') . 'sigh=' . rawurlencode($sig);
            // Durée du sheet : le dernier a moins de frames
            $isLast      = ($seg === $numSheets - 1);
            $framesHere  = $isLast ? ($count - $seg * $framesPerSheet) : $framesPerSheet;
            $sheetDur    = $perFrameSec > 0 ? round($framesHere * $perFrameSec, 3) : null;
            $sheets[] = ['url' => $sheetUrl, 'duration' => $sheetDur];
        }

        $levels[] = [
            'level'    => $idx,
            'width'    => $w,
            'height'   => $h,
            'count'    => $count,
            'cols'     => $cols,
            'rows'     => $rows,
            'interval' => $interval,
            'sheets'   => $sheets,
            'source'   => 'spec',
        ];
    }

    return $levels;
}

// Collecte TOUTES les images disponibles
function collectThumbnails(string $videoId, ?array $ytdlpData): array
{
    $seen  = [];
    $thumbs = [];

    $add = function(string $url, ?int $w, ?int $h, string $label) use (&$seen, &$thumbs) {
        $url = preg_replace('/\?.*$/', '', $url); // retire les query strings de i.ytimg
        if (isset($seen[$url])) return;
        $seen[$url] = true;
        $thumbs[] = ['url' => $url, 'width' => $w, 'height' => $h, 'label' => $label];
    };

    // 1. Thumbnails yt-dlp (les plus complètes)
    if ($ytdlpData) {
        foreach ($ytdlpData['thumbnails'] ?? [] as $t) {
            if (empty($t['url'])) continue;
            $add($t['url'], $t['width'] ?? null, $t['height'] ?? null,
                 isset($t['width']) ? ($t['width'].'×'.($t['height']??'?')) : ($t['id'] ?? ''));
        }
    }

    // 2. URLs standard i.ytimg.com (jpg)
    $stdNames = [
        'maxresdefault' => [1280, 720],
        'sddefault'     => [640,  480],
        'hqdefault'     => [480,  360],
        'mqdefault'     => [320,  180],
        'default'       => [120,   90],
        '0'             => [480,  360],
        '1'             => [120,   90],
        '2'             => [120,   90],
        '3'             => [120,   90],
    ];
    foreach ($stdNames as $name => [$w, $h]) {
        $add("https://i.ytimg.com/vi/{$videoId}/{$name}.jpg", $w, $h, $name);
        $add("https://i.ytimg.com/vi_webp/{$videoId}/{$name}.webp", $w, $h, $name.'.webp');
    }

    // Trier par résolution décroissante
    usort($thumbs, function($a, $b) {
        $pa = ($a['width'] ?? 0) * ($a['height'] ?? 0);
        $pb = ($b['width'] ?? 0) * ($b['height'] ?? 0);
        return $pb - $pa;
    });

    return $thumbs;
}
