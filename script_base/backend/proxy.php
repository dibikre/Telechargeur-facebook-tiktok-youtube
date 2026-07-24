<?php

/**
 * Proxy HTTP pour le rechargement et le relais de flux vidéo avec support des en-têtes Range.
 */

header('Access-Control-Allow-Origin: *');
error_reporting(0);

$urlDistante = isset($_GET['url']) ? trim($_GET['url']) : '';
$nomTelechargement = isset($_GET['nom_fichier']) ? trim($_GET['nom_fichier']) : 'video.mp4';

if (!$urlDistante) {
    http_response_code(400);
    exit('URL manquante');
}

$hoteAnalyse = parse_url($urlDistante, PHP_URL_HOST) ?? '';

// Validation de l'hôte pour des raisons de sécurité
$domainesAutorises = ['googlevideo.com', 'fbcdn.net', 'tiktokcdn.com', 'muscdn.com'];
$estDomaineValide  = false;

foreach ($domainesAutorises as $domaineValide) {
    if (str_contains($hoteAnalyse, $domaineValide)) {
        $estDomaineValide = true;
        break;
    }
}

if (!$estDomaineValide) {
    http_response_code(403);
    exit('Domaine non autorisé pour le relayage');
}

$agentUtilisateur = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';

$enTetesCurl = [
    'User-Agent: ' . $agentUtilisateur,
    'Accept: */*',
    'Accept-Encoding: identity',
    'Connection: keep-alive',
];

if (!empty($_SERVER['HTTP_RANGE'])) {
    $enTetesCurl[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
}

$codeStatutReponse = 200;
$enTetesEnvoyes    = false;
$enTetesCollectes  = [];

$sessionCurl = curl_init();
curl_setopt_array($sessionCurl, [
    CURLOPT_URL            => $urlDistante,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => $enTetesCurl,
    CURLOPT_BUFFERSIZE     => 65536,

    CURLOPT_HEADERFUNCTION => function ($session, $ligneEnTete) use (&$codeStatutReponse, &$enTetesCollectes) {
        $chaineNettoyee = rtrim($ligneEnTete);

        if (preg_match('/^HTTP\/\d[\d.]* (\d{3})/', $chaineNettoyee, $matchCode)) {
            $codeStatutReponse = (int)$matchCode[1];
            return strlen($ligneEnTete);
        }

        $listeRelais = ['content-type:', 'content-length:', 'content-range:', 'accept-ranges:'];
        $chaineMinuscule = strtolower($chaineNettoyee);

        foreach ($listeRelais as $prefixeRelais) {
            if (str_starts_with($chaineMinuscule, $prefixeRelais)) {
                $enTetesCollectes[] = $chaineNettoyee;
                break;
            }
        }
        return strlen($ligneEnTete);
    },

    CURLOPT_WRITEFUNCTION => function ($session, $blocDonnees) use (&$enTetesEnvoyes, &$codeStatutReponse, &$enTetesCollectes) {
        if (!$enTetesEnvoyes) {
            http_response_code($codeStatutReponse);
            foreach ($enTetesCollectes as $enTeteRelaye) {
                header($enTeteRelaye);
            }
            $enTetesEnvoyes = true;
        }
        echo $blocDonnees;
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        return strlen($blocDonnees);
    },
]);

curl_exec($sessionCurl);
curl_close($sessionCurl);
