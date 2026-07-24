<?php

namespace Backend\Traitements;

use Backend\Utilitaires\ExecuteurYtdlp;

/**
 * Récupérateur spécialisé pour la plateforme Facebook.
 */
class RecuperateurFacebook extends RecuperateurAbstrait
{
    /**
     * @param string $urlVideo
     * @return bool
     */
    public function correspondA(string $urlVideo): bool
    {
        return (bool)preg_match('#(facebook\.com|fb\.watch|fb\.gg)#i', $urlVideo);
    }

    /**
     * @param string $urlVideo
     * @return array
     */
    public function extraire(string $urlVideo): array
    {
        $donneesYtdlp = ExecuteurYtdlp::extraireFormationsJson($urlVideo);

        if ($donneesYtdlp !== null) {
            return [
                'succes'  => true,
                'donnees' => $this->construireDepuisYtdlp($donneesYtdlp, $urlVideo)
            ];
        }

        // Tentative de secours via scraping direct
        $donneesScrape = RecuperateurFacebookScraping::extraireParScraping($urlVideo);

        if (!empty($donneesScrape['formats']['progressive'])) {
            return [
                'succes'  => true,
                'donnees' => $donneesScrape
            ];
        }

        return [
            'succes' => false,
            'erreur' => 'Aucune vidéo publique trouvée sur cette URL Facebook.'
        ];
    }

    /**
     * Construit le tableau d'extraction à partir de yt-dlp.
     *
     * @param array $donneesBrutes
     * @param string $urlOrigine
     * @return array
     */
    private function construireDepuisYtdlp(array $donneesBrutes, string $urlOrigine): array
    {
        $identifiantVideo   = $donneesBrutes['id'] ?? null;
        $formatsProgressifs = [];
        $formatsDashVideo   = [];
        $formatsDashAudio   = [];
        $fluxEnDirect       = [];

        foreach ($donneesBrutes['formats'] ?? [] as $formatCourant) {
            $idFormat       = $formatCourant['format_id'] ?? '';
            $codecVideo     = $formatCourant['vcodec']    ?? 'none';
            $codecAudio     = $formatCourant['acodec']    ?? 'none';
            $urlTelecharge  = $formatCourant['url']       ?? '';
            $extension      = $formatCourant['ext']       ?? 'mp4';
            $noteFormat     = $formatCourant['format_note'] ?? '';
            $protocole      = $formatCourant['protocol']  ?? '';

            if (!$urlTelecharge) {
                continue;
            }

            if (in_array($protocole, ['m3u8', 'm3u8_native', 'dash']) && ($donneesBrutes['is_live'] ?? false)) {
                $fluxEnDirect[] = [
                    'id'         => $idFormat,
                    'url'        => $urlTelecharge,
                    'ext'        => $extension,
                    'protocol'   => $protocole,
                    'resolution' => $formatCourant['resolution'] ?? null,
                ];
                continue;
            }

            if (in_array($idFormat, ['sd', 'hd'])) {
                $formatsProgressifs[] = [
                    'id'      => $idFormat,
                    'libelle' => strtoupper($idFormat),
                    'url'     => $urlTelecharge,
                    'ext'     => $extension,
                    'largeur' => $formatCourant['width']  ?? null,
                    'hauteur' => $formatCourant['height'] ?? null,
                ];
                continue;
            }

            if ($codecVideo !== 'none' && $codecAudio === 'none' && str_contains($noteFormat, 'DASH')) {
                $formatsDashVideo[] = [
                    'id'          => $idFormat,
                    'url'         => $urlTelecharge,
                    'ext'         => $extension,
                    'largeur'     => $formatCourant['width']  ?? null,
                    'hauteur'     => $formatCourant['height'] ?? null,
                    'codec_video' => $codecVideo,
                    'debit'       => $formatCourant['tbr'] ?? null,
                ];
                continue;
            }

            if ($codecVideo === 'none' && $codecAudio !== 'none' && str_contains($noteFormat, 'DASH')) {
                $formatsDashAudio[] = [
                    'id'          => $idFormat,
                    'url'         => $urlTelecharge,
                    'ext'         => $extension,
                    'codec_audio' => $codecAudio,
                ];
            }
        }

        usort($formatsDashVideo, fn($elemA, $elemB) => ($elemB['debit'] ?? 0) <=> ($elemA['debit'] ?? 0));

        $tableVuesMiniatures = [];
        $listeMiniatures     = [];
        if ($donneesBrutes['thumbnail'] ?? null) {
            $tableVuesMiniatures[$donneesBrutes['thumbnail']] = true;
            $listeMiniatures[] = $donneesBrutes['thumbnail'];
        }
        foreach ($donneesBrutes['thumbnails'] ?? [] as $elementMiniature) {
            $urlMiniature = $elementMiniature['url'] ?? null;
            if ($urlMiniature && !isset($tableVuesMiniatures[$urlMiniature])) {
                $tableVuesMiniatures[$urlMiniature] = true;
                $listeMiniatures[] = $urlMiniature;
            }
        }

        $urlAuteur = $donneesBrutes['uploader_url'] ?? null;
        if (!$urlAuteur && ($donneesBrutes['uploader_id'] ?? null)) {
            $identifiantAuteur = $donneesBrutes['uploader_id'];
            $urlAuteur = is_numeric($identifiantAuteur)
                ? "https://www.facebook.com/profile.php?id={$identifiantAuteur}"
                : "https://www.facebook.com/{$identifiantAuteur}";
        }

        $nomFichierMiroir = $this->sauvegarderMiroirJson($identifiantVideo, $donneesBrutes);

        return [
            'identifiant_video'   => $identifiantVideo,
            'titre'               => $donneesBrutes['title'] ?? 'Vidéo Facebook',
            'description'         => $donneesBrutes['description'] ?? null,
            'auteur'              => $donneesBrutes['uploader'] ?? null,
            'identifiant_auteur'  => $donneesBrutes['uploader_id'] ?? null,
            'url_auteur'          => $urlAuteur,
            'miniature_principale'=> $listeMiniatures[0] ?? null,
            'miniatures'          => $listeMiniatures,
            'nombre_vues'         => $donneesBrutes['view_count'] ?? null,
            'est_en_direct'       => (bool)($donneesBrutes['is_live'] ?? false),
            'duree_secondes'      => $donneesBrutes['duration'] ?? null,
            'chaine_duree'        => $donneesBrutes['duration_string'] ?? null,
            'date_publication'    => $this->formaterDatePublication($donneesBrutes['upload_date'] ?? null),
            'formats' => [
                'progressive' => $formatsProgressifs,
                'dash_video'  => $formatsDashVideo,
                'dash_audio'  => $formatsDashAudio,
                'live'        => $fluxEnDirect,
            ],
            'captions'            => $this->extraireCaptions($donneesBrutes),
            'fichier_json'        => $nomFichierMiroir,
            'url_originale'       => $donneesBrutes['original_url'] ?? $urlOrigine,
            'plateforme'          => 'facebook',
            'source'              => 'ytdlp'
        ];
    }
}
