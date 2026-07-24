<?php
/**
 * wr/proxy.php — Proxy HTTP pour whoreshub.com
 * Permet à FFmpeg d'accéder aux URLs protégées par Referer/cookie check,
 * avec support complet des Range requests (lecture partielle mp4).
 *
 * Params GET :
 *   url     = URL cible (encodée via encodeURIComponent)
 *   cookies = cookies du navigateur (document.cookie, optionnel)
 *   ref     = Referer à envoyer (défaut : https://www.whoreshub.com/)
 */
header('Access-Control-Allow-Origin: *');
error_reporting(0);

// ── Validation ────────────────────────────────────────────────────────────────
$rawUrl  = isset($_GET['url']) ? trim($_GET['url']) : '';
$cookies = isset($_GET['cookies']) ? trim($_GET['cookies']) : '';
$referer = isset($_GET['ref']) ? trim($_GET['ref']) : 'https://www.whoreshub.com/';

if (!$rawUrl) { http_response_code(400); exit('Missing url'); }

$targetUrl = $rawUrl; // déjà décodé par PHP
$host      = parse_url($targetUrl, PHP_URL_HOST) ?? '';

// Whitelist : uniquement whoreshub.com et son CDN
if (!preg_match('/\.(whoreshub\.com|cdntrex\.com)$/i', $host)) {
    http_response_code(403);
    exit('URL non autorisée (hors domaine whoreshub)');
}

// ── Headers à forwarder ───────────────────────────────────────────────────────
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

$curlHeaders = [
    'User-Agent: ' . $ua,
    'Referer: '    . $referer,
    'Origin: https://www.whoreshub.com',
    'Accept: */*',
    'Accept-Encoding: identity',  // évite la compression pour le streaming
    'Connection: keep-alive',
];

// Cookie du navigateur (depuis document.cookie)
if ($cookies) {
    $curlHeaders[] = 'Cookie: ' . $cookies;
}

// Range header forwarding (pour les requêtes partielles de FFmpeg)
$rangeHeader = '';
if (!empty($_SERVER['HTTP_RANGE'])) {
    $rangeHeader   = $_SERVER['HTTP_RANGE'];
    $curlHeaders[] = 'Range: ' . $rangeHeader;
}

// ── cURL streaming ─────────────────────────────────────────────────────────────
$responseCode    = 200;
$headersSent     = false;
$collectedHeaders = [];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $targetUrl,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => $curlHeaders,
    CURLOPT_BUFFERSIZE     => 65536,

    // Capture des headers de réponse
    CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseCode, &$collectedHeaders, &$headersSent) {
        $trimmed = rtrim($line);

        // Ligne de statut HTTP
        if (preg_match('/^HTTP\/\d[\d.]* (\d{3})/', $trimmed, $m)) {
            $responseCode = (int) $m[1];
            return strlen($line);
        }

        // Headers à relayer vers FFmpeg
        $lower = strtolower($trimmed);
        $relay = [
            'content-type:',
            'content-length:',
            'content-range:',
            'accept-ranges:',
            'last-modified:',
            'etag:',
        ];
        foreach ($relay as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $collectedHeaders[] = $trimmed;
                break;
            }
        }
        return strlen($line);
    },

    // Stream du body dès réception
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$headersSent, &$responseCode, &$collectedHeaders) {
        if (!$headersSent) {
            http_response_code($responseCode);
            foreach ($collectedHeaders as $h) {
                header($h);
            }
            $headersSent = true;
        }
        echo $chunk;
        if (ob_get_level()) { ob_flush(); }
        flush();
        return strlen($chunk);
    },
]);

curl_exec($ch);
$curlErr = curl_error($ch);
curl_close($ch);

// Émettre les headers si aucun body n'a été reçu (ex: 403 sans body)
if (!$headersSent) {
    http_response_code($responseCode ?: 502);
    foreach ($collectedHeaders as $h) header($h);
    if ($curlErr) echo 'cURL error: ' . $curlErr;
}
