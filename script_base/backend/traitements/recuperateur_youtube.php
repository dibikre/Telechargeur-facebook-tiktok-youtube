<?php

namespace Backend\Traitements;

use Backend\Utilitaires\ExecuteurYtdlp;

/**
 * Récupérateur spécialisé pour la plateforme YouTube.
 */
class RecuperateurYouTube extends RecuperateurAbstrait
{
    /**
     * @param string $urlVideo
     * @return bool
     */
    public function correspondA(string $urlVideo): bool
    {
        return (bool)preg_match('#(youtube\.com|youtu\.be)#i', $urlVideo);
    }

    /**
     * @param string $urlVideo
     * @return array
     */
    public function extraire(string $urlVideo): array
    {
        $identifiantVideo = $this->extraireIdentifiant($urlVideo);
        if (!$identifiantVideo) {
            return [
                'succes' => false,
                'erreur' => 'Impossible d\'extraire l\'identifiant de la vidéo YouTube.'
            ];
        }

        $fichierCookies = __DIR__ . '/../stockage/cookies_yt.txt';
        $donneesYtdlp   = ExecuteurYtdlp::extraireFormationsJson($urlVideo, $fichierCookies);

        if ($donneesYtdlp !== null) {
            return [
                'succes'  => true,
                'donnees' => $this->construireDepuisYtdlp($donneesYtdlp, $identifiantVideo, $urlVideo)
            ];
        }

        // Fallback via InnerTube API
        $donneesInnerTube = RecuperateurYouTubeInnertube::extraireViaApiInnerTube($identifiantVideo, $fichierCookies);
        if ($donneesInnerTube !== null) {
            $detailsVideo = $donneesInnerTube['videoDetails'] ?? [];
            return [
                'succes'  => true,
                'donnees' => [
                    'identifiant_video'   => $identifiantVideo,
                    'titre'               => $detailsVideo['title'] ?? 'Vidéo YouTube',
                    'auteur'              => $detailsVideo['author'] ?? null,
                    'duree_secondes'      => isset($detailsVideo['lengthSeconds']) ? (int)$detailsVideo['lengthSeconds'] : null,
                    'miniature_principale'=> "https://i.ytimg.com/vi/{$identifiantVideo}/maxresdefault.jpg",
                    'miniatures'          => RecuperateurYouTubeInnertube::collecterTableauMiniatures($identifiantVideo),
                    'formats' => [
                        'progressive' => $donneesInnerTube['streamingData']['formats'] ?? [],
                        'adaptive'    => $donneesInnerTube['streamingData']['adaptiveFormats'] ?? []
                    ],
                    'url_originale'       => $urlVideo,
                    'plateforme'          => 'youtube',
                    'source'              => 'innertube'
                ]
            ];
        }

        return [
            'succes' => false,
            'erreur' => 'Échec de l\'extraction de la vidéo YouTube.'
        ];
    }

    /**
     * Extrait l'identifiant YouTube de 11 caractères depuis l'URL.
     *
     * @param string $urlVideo
     * @return string|null
     */
    private function extraireIdentifiant(string $urlVideo): ?string
    {
        if (preg_match('#(?:v=|youtu\.be/|/shorts/|/live/)([A-Za-z0-9_\-]{11})#', $urlVideo, $correspondance)) {
            return $correspondance[1];
        }
        return null;
    }

    /**
     * Structure les données extraites via yt-dlp.
     *
     * @param array $donneesBrutes
     * @param string $identifiantVideo
     * @param string $urlOrigine
     * @return array
     */
    private function construireDepuisYtdlp(array $donneesBrutes, string $identifiantVideo, string $urlOrigine): array
    {
        [$formatsProgressifs, $formatsAdaptatifs] = $this->analyserFormatsYtdlp($donneesBrutes['formats'] ?? []);

        $listeMiniatures = RecuperateurYouTubeInnertube::collecterTableauMiniatures($identifiantVideo);
        $nomFichierMiroir = $this->sauvegarderMiroirJson($identifiantVideo, $donneesBrutes);

        return [
            'identifiant_video'   => $identifiantVideo,
            'titre'               => $donneesBrutes['title'] ?? 'Vidéo YouTube',
            'description'         => $donneesBrutes['description'] ?? null,
            'auteur'              => $donneesBrutes['uploader'] ?? ($donneesBrutes['channel'] ?? null),
            'url_chaine'          => $donneesBrutes['channel_url'] ?? ($donneesBrutes['uploader_url'] ?? null),
            'duree_secondes'      => isset($donneesBrutes['duration']) ? (int)$donneesBrutes['duration'] : null,
            'nombre_vues'         => $donneesBrutes['view_count'] ?? null,
            'nombre_jaime'        => $donneesBrutes['like_count'] ?? null,
            'date_publication'    => $this->formaterDatePublication($donneesBrutes['upload_date'] ?? null),
            'miniature_principale'=> $donneesBrutes['thumbnail'] ?? ($listeMiniatures[0]['url'] ?? null),
            'miniatures'          => $listeMiniatures,
            'est_en_direct'       => (bool)($donneesBrutes['is_live'] ?? false),
            'formats' => [
                'progressive' => $formatsProgressifs,
                'adaptive'    => $formatsAdaptatifs
            ],
            'captions'            => $this->extraireCaptions($donneesBrutes),
            'fichier_json'        => $nomFichierMiroir,
            'url_originale'       => $urlOrigine,
            'plateforme'          => 'youtube',
            'source'              => 'ytdlp'
        ];
    }

    /**
     * Sépare les formats progressifs des formats adaptatifs (audio/vidéo séparés).
     *
     * @param array $listeFormatsBruts
     * @return array
     */
    private function analyserFormatsYtdlp(array $listeFormatsBruts): array
    {
        $formatsProgressifs = [];
        $formatsAdaptatifs  = [];

        foreach ($listeFormatsBruts as $formatCourant) {
            $codecVideo   = $formatCourant['vcodec'] ?? 'none';
            $codecAudio   = $formatCourant['acodec'] ?? 'none';
            $aDuVideo     = ($codecVideo !== 'none' && $codecVideo !== null);
            $aDeLAudio    = ($codecAudio !== 'none' && $codecAudio !== null);

            if (!$aDuVideo && !$aDeLAudio) {
                continue;
            }

            $elementFormat = [
                'itag'          => $formatCourant['format_id'] ?? null,
                'conteneur'     => $formatCourant['ext']       ?? null,
                'codec_video'   => $aDuVideo ? $codecVideo : null,
                'codec_audio'   => $aDeLAudio ? $codecAudio : null,
                'qualite'       => $formatCourant['format_note'] ?? ($formatCourant['height'] ? $formatCourant['height'].'p' : null),
                'largeur'       => $formatCourant['width']  ?? null,
                'hauteur'       => $formatCourant['height'] ?? null,
                'fps'           => isset($formatCourant['fps']) ? (int)$formatCourant['fps'] : null,
                'debit'         => isset($formatCourant['tbr']) ? (int)($formatCourant['tbr'] * 1000) : null,
                'taille'        => $formatCourant['filesize'] ?? ($formatCourant['filesize_approx'] ?? null),
                'url'           => $formatCourant['url'] ?? null
            ];

            if ($aDuVideo && $aDeLAudio) {
                $formatsProgressifs[] = $elementFormat;
            } else {
                $formatsAdaptatifs[] = $elementFormat;
            }
        }

        usort($formatsProgressifs, fn($elemA, $elemB) => ($elemB['hauteur'] ?? 0) <=> ($elemA['hauteur'] ?? 0));
        usort($formatsAdaptatifs, fn($elemA, $elemB) => ($elemB['hauteur'] ?? 0) <=> ($elemA['hauteur'] ?? 0));

        return [$formatsProgressifs, $formatsAdaptatifs];
    }
}
