<?php

namespace Backend\Traitements;

use Backend\Utilitaires\ExecuteurYtdlp;

/**
 * Récupérateur spécialisé pour la plateforme TikTok.
 */
class RecuperateurTikTok extends RecuperateurAbstrait
{
    /**
     * @param string $urlVideo
     * @return bool
     */
    public function correspondA(string $urlVideo): bool
    {
        return (bool)preg_match('#(tiktok\.com|vm\.tiktok\.com)#i', $urlVideo);
    }

    /**
     * @param string $urlVideo
     * @return array
     */
    public function extraire(string $urlVideo): array
    {
        $donneesYtdlp = ExecuteurYtdlp::extraireFormationsJson($urlVideo);

        if ($donneesYtdlp === null) {
            return [
                'succes' => false,
                'erreur' => 'Impossible d\'extraire la vidéo TikTok. Vérifiez que l\'URL est publique.'
            ];
        }

        return [
            'succes' => true,
            'donnees' => $this->construireDepuisYtdlp($donneesYtdlp, $urlVideo)
        ];
    }

    /**
     * Structure le tableau de données d'après le résultat yt-dlp.
     *
     * @param array $donneesBrutes
     * @param string $urlOrigine
     * @return array
     */
    private function construireDepuisYtdlp(array $donneesBrutes, string $urlOrigine): array
    {
        $identifiantVideo = $donneesBrutes['id'] ?? null;
        $formatsVideo     = [];
        $formatsAudio     = [];
        $fluxDirects      = [];

        foreach ($donneesBrutes['formats'] ?? [] as $formatCourant) {
            $idFormat       = $formatCourant['format_id'] ?? '';
            $codecVideo     = $formatCourant['vcodec']    ?? 'none';
            $codecAudio     = $formatCourant['acodec']    ?? 'none';
            $urlTelecharge  = $formatCourant['url']       ?? '';
            $extension      = $formatCourant['ext']       ?? 'mp4';
            $protocole      = $formatCourant['protocol']  ?? '';

            if (!$urlTelecharge) {
                continue;
            }

            if (in_array($protocole, ['m3u8', 'm3u8_native', 'dash']) && ($donneesBrutes['is_live'] ?? false)) {
                $fluxDirects[] = [
                    'id'         => $idFormat,
                    'url'        => $urlTelecharge,
                    'ext'        => $extension,
                    'protocol'   => $protocole,
                    'resolution' => $formatCourant['resolution'] ?? null,
                    'tbr'        => $formatCourant['tbr'] ?? null,
                ];
                continue;
            }

            if ($codecVideo === 'none' && $codecAudio !== 'none') {
                $formatsAudio[] = [
                    'id'        => $idFormat,
                    'url'       => $urlTelecharge,
                    'ext'       => $extension,
                    'codec_audio' => $codecAudio,
                    'asr'       => $formatCourant['asr'] ?? null,
                    'debit'     => isset($formatCourant['abr']) ? round($formatCourant['abr'], 1) : null,
                    'taille'    => $formatCourant['filesize'] ?? $formatCourant['filesize_approx'] ?? null,
                ];
                continue;
            }

            if ($codecVideo !== 'none') {
                $noteFormat = $formatCourant['format_note'] ?? '';
                $formatsVideo[] = [
                    'id'            => $idFormat,
                    'url'           => $urlTelecharge,
                    'ext'           => $extension,
                    'largeur'       => $formatCourant['width']  ?? null,
                    'hauteur'       => $formatCourant['height'] ?? null,
                    'resolution'    => $formatCourant['resolution'] ?? (($formatCourant['width'] ?? null) ? "{$formatCourant['width']}x{$formatCourant['height']}" : null),
                    'codec_video'   => $codecVideo,
                    'codec_audio'   => $codecAudio,
                    'avec_audio'    => ($codecAudio !== 'none'),
                    'debit'         => isset($formatCourant['tbr']) ? round($formatCourant['tbr'], 1) : null,
                    'fps'           => $formatCourant['fps'] ?? null,
                    'taille'        => $formatCourant['filesize'] ?? $formatCourant['filesize_approx'] ?? null,
                    'filigrane'     => str_contains(strtolower($noteFormat), 'watermark'),
                ];
            }
        }

        usort($formatsVideo, function ($elementA, $elementB) {
            $diffHauteur = ($elementB['hauteur'] ?? 0) <=> ($elementA['hauteur'] ?? 0);
            return $diffHauteur !== 0 ? $diffHauteur : ($elementB['debit'] ?? 0) <=> ($elementA['debit'] ?? 0);
        });

        $tableVues = [];
        $listeMiniatures = [];
        if ($donneesBrutes['thumbnail'] ?? null) {
            $tableVues[$donneesBrutes['thumbnail']] = true;
            $listeMiniatures[] = $donneesBrutes['thumbnail'];
        }
        foreach ($donneesBrutes['thumbnails'] ?? [] as $elementMiniature) {
            $urlMiniature = $elementMiniature['url'] ?? null;
            if ($urlMiniature && !isset($tableVues[$urlMiniature])) {
                $tableVues[$urlMiniature] = true;
                $listeMiniatures[] = $urlMiniature;
            }
        }

        $donneesPisteMusique = null;
        if ($donneesBrutes['track'] ?? null) {
            $donneesPisteMusique = [
                'titre'    => $donneesBrutes['track']  ?? null,
                'artiste'  => $donneesBrutes['artist'] ?? ($donneesBrutes['creator'] ?? null),
                'album'    => $donneesBrutes['album']   ?? null,
            ];
        }

        $nomFichierMiroir = $this->sauvegarderMiroirJson($identifiantVideo, $donneesBrutes);
        $estEnDirect      = (bool)($donneesBrutes['is_live'] ?? false);

        return [
            'identifiant_video'   => $identifiantVideo,
            'titre'               => $donneesBrutes['title'] ?? ($donneesBrutes['description'] ?? 'Vidéo TikTok'),
            'description'         => $donneesBrutes['description'] ?? null,
            'auteur'              => $donneesBrutes['uploader']    ?? ($donneesBrutes['creator'] ?? null),
            'identifiant_auteur'  => $donneesBrutes['uploader_id'] ?? null,
            'url_auteur'          => $donneesBrutes['uploader_url'] ?? (isset($donneesBrutes['uploader_id']) ? "https://www.tiktok.com/@{$donneesBrutes['uploader_id']}" : null),
            'miniature_principale'=> $listeMiniatures[0] ?? null,
            'miniatures'          => $listeMiniatures,
            'nombre_vues'         => $donneesBrutes['view_count']    ?? null,
            'nombre_jaime'        => $donneesBrutes['like_count']    ?? null,
            'nombre_commentaires' => $donneesBrutes['comment_count'] ?? null,
            'est_en_direct'       => $estEnDirect,
            'duree_secondes'      => $donneesBrutes['duration'] ?? null,
            'chaine_duree'        => $donneesBrutes['duration_string'] ?? null,
            'date_publication'    => $this->formaterDatePublication($donneesBrutes['upload_date'] ?? null),
            'musique'             => $donneesPisteMusique,
            'formats' => [
                'video' => $formatsVideo,
                'audio' => $formatsAudio,
                'live'  => $fluxDirects,
            ],
            'fichier_json'        => $nomFichierMiroir,
            'url_originale'       => $donneesBrutes['original_url'] ?? $urlOrigine,
            'plateforme'          => 'tiktok'
        ];
    }
}
