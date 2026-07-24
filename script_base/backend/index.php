<?php

require_once __DIR__ . '/interfaces/interface_recuperateur.php';
require_once __DIR__ . '/utilitaires/reponse_json.php';
require_once __DIR__ . '/utilitaires/executeur_ytdlp.php';
require_once __DIR__ . '/utilitaires/client_http.php';
require_once __DIR__ . '/traitements/recuperateur_abstrait.php';
require_once __DIR__ . '/traitements/recuperateur_facebook_scraping.php';
require_once __DIR__ . '/traitements/recuperateur_facebook.php';
require_once __DIR__ . '/traitements/recuperateur_tiktok.php';
require_once __DIR__ . '/traitements/recuperateur_youtube_innertube.php';
require_once __DIR__ . '/traitements/recuperateur_youtube.php';

use Backend\Utilitaires\ReponseJson;
use Backend\Traitements\RecuperateurFacebook;
use Backend\Traitements\RecuperateurTikTok;
use Backend\Traitements\RecuperateurYouTube;

set_time_limit(180);
error_reporting(0);

// Gestion des requêtes préliminaires CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ReponseJson::envoyerOptions();
}

// Lecture des paramètres entrants (JSON body, POST ou GET)
$fluxEntrant       = file_get_contents('php://input');
$donneesCorps      = json_decode($fluxEntrant ?: '', true);
$urlVideoSaisie    = trim($donneesCorps['url'] ?? $_POST['url'] ?? $_GET['url'] ?? '');
$plateformeDemandee = trim($donneesCorps['plateforme'] ?? $_POST['plateforme'] ?? $_GET['plateforme'] ?? '');

if (!$urlVideoSaisie) {
    ReponseJson::envoyerErreur('Veuillez fournir une URL de vidéo valide.');
}

// Enregistrement des récuperateurs enregistrés selon les principes SOLID
$listeRecuperateurs = [
    new RecuperateurFacebook(),
    new RecuperateurTikTok(),
    new RecuperateurYouTube(),
];

// Recherche du récupérateur approprié
$recuperateurTrouve = null;

if ($plateformeDemandee) {
    foreach ($listeRecuperateurs as $recuperateurCourant) {
        if ($recuperateurCourant->correspondA($plateformeDemandee) || $recuperateurCourant->correspondA($urlVideoSaisie)) {
            $recuperateurTrouve = $recuperateurCourant;
            break;
        }
    }
}

if (!$recuperateurTrouve) {
    foreach ($listeRecuperateurs as $recuperateurCourant) {
        if ($recuperateurCourant->correspondA($urlVideoSaisie)) {
            $recuperateurTrouve = $recuperateurCourant;
            break;
        }
    }
}

if (!$recuperateurTrouve) {
    ReponseJson::envoyerErreur('Plateforme non prise en charge. Seuls Facebook, TikTok et YouTube sont supportés.');
}

// Exécution de l'extraction
$resultatExtraction = $recuperateurTrouve->extraire($urlVideoSaisie);

if (!empty($resultatExtraction['succes'])) {
    ReponseJson::envoyerSucces($resultatExtraction['donnees']);
} else {
    ReponseJson::envoyerErreur($resultatExtraction['erreur'] ?? 'Échec du traitement de la vidéo.');
}
