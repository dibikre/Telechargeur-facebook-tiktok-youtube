<?php

namespace Backend\Traitements;

use Backend\Utilitaires\ClientHttp;

/**
 * Traitement de secours par scraping HTML pour Facebook.
 */
class RecuperateurFacebookScraping
{
    /**
     * Effectue l'extraction via scraping de la page HTML.
     *
     * @param string $urlVideo
     * @return array
     */
    public static function extraireParScraping(string $urlVideo): array
    {
        $urlCible = self::normaliserUrlFacebook($urlVideo);
        $fichierCookiesTemp = tempnam(sys_get_temp_dir(), 'fb_cook_');

        $resultatHttp = ClientHttp::effectuerRequeteGet($urlCible, $fichierCookiesTemp);
        @unlink($fichierCookiesTemp);

        if ($resultatHttp['code'] !== 0 || strlen($resultatHttp['html']) < 500) {
            return [];
        }

        $htmlComplet = $resultatHttp['html'];
        $urlFinale   = $resultatHttp['url'];

        $identifiantVideo = null;
        if (preg_match('#(?:v=|/videos/|/reel/)(\d{10,})#', $urlFinale, $correspondances)) {
            $identifiantVideo = $correspondances[1];
        }

        $urlQualiteHaute = self::rechercherUrlChamp($htmlComplet, 'browser_native_hd_url')
            ?: self::rechercherUrlChamp($htmlComplet, 'playable_url_quality_hd');

        $urlQualiteBasse = self::rechercherUrlChamp($htmlComplet, 'browser_native_sd_url')
            ?: self::rechercherUrlChamp($htmlComplet, 'playable_url');

        $formatsProgressifs = [];
        if ($urlQualiteHaute) {
            $formatsProgressifs[] = ['id' => 'hd', 'libelle' => 'HD', 'url' => $urlQualiteHaute, 'ext' => 'mp4'];
        }
        if ($urlQualiteBasse && $urlQualiteBasse !== $urlQualiteHaute) {
            $formatsProgressifs[] = ['id' => 'sd', 'libelle' => 'SD', 'url' => $urlQualiteBasse, 'ext' => 'mp4'];
        }

        $titrePage = self::rechercherUrlChamp($htmlComplet, 'title');
        if (!$titrePage && preg_match('#<title[^>]*>(.*?)</title>#si', $htmlComplet, $matchTitre)) {
            $titrePage = preg_replace('/\s*\|\s*Facebook.*$/i', '', html_entity_decode(trim(strip_tags($matchTitre[1])), ENT_QUOTES, 'UTF-8'));
        }

        return [
            'identifiant_video'    => $identifiantVideo,
            'titre'                => $titrePage ? trim($titrePage) : 'Vidéo Facebook',
            'miniature_principale' => self::rechercherUrlChamp($htmlComplet, 'preferred_thumbnail'),
            'formats' => [
                'progressive' => $formatsProgressifs,
                'dash_video'  => [],
                'dash_audio'  => [],
                'live'        => [],
            ],
            'url_originale'        => $urlFinale,
            'plateforme'           => 'facebook',
            'source'               => 'scraping'
        ];
    }

    /**
     * Normalise l'URL pour pointer vers la page de la vidéo.
     *
     * @param string $urlFacebook
     * @return string
     */
    private static function normaliserUrlFacebook(string $urlFacebook): string
    {
        if (preg_match('#facebook\.com/watch[/?].*[?&]v=(\d+)#', $urlFacebook, $m) ||
            preg_match('#facebook\.com/video/embed[/?].*video_id=(\d+)#', $urlFacebook, $m) ||
            preg_match('#facebook\.com/(?:reel|video|reels)/(\d+)#', $urlFacebook, $m)) {
            return "https://www.facebook.com/watch/?v={$m[1]}&_rdr";
        }
        return $urlFacebook;
    }

    /**
     * Recherche une valeur de champ JSON décodée dans le code HTML.
     *
     * @param string $html
     * @param string $cle
     * @return string|null
     */
    private static function rechercherUrlChamp(string $html, string $cle): ?string
    {
        if (preg_match('/"' . preg_quote($cle, '/') . '"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $html, $m)) {
            $chaineDecodee = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($mat) => mb_chr(hexdec($mat[1]), 'UTF-8'), $m[1]);
            $chaineDecodee = str_replace('\\/', '/', $chaineDecodee);
            return html_entity_decode($chaineDecodee, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }
}
