<?php

namespace Backend\Traitements;

use Backend\Utilitaires\ClientHttp;

/**
 * Service de secours pour l'extraction YouTube via InnerTube API.
 */
class RecuperateurYouTubeInnertube
{
    /**
     * Tente la récupération via les clients Android/iOS d'InnerTube.
     *
     * @param string $identifiantVideo
     * @param string $fichierCookies
     * @return array|null
     */
    public static function extraireViaApiInnerTube(string $identifiantVideo, string $fichierCookies): ?array
    {
        $clientsInnerTube = [
            [
                'nom'    => 'ANDROID',
                'id'     => '3',
                'ver'    => '19.09.37',
                'agent'  => 'com.google.android.youtube/19.09.37 (Linux; U; Android 13; Pixel 7) gzip',
                'contexte' => [
                    'clientName'        => 'ANDROID',
                    'clientVersion'     => '19.09.37',
                    'androidSdkVersion' => 33,
                    'userAgent'         => 'com.google.android.youtube/19.09.37 (Linux; U; Android 13; Pixel 7) gzip',
                    'osName'            => 'Android',
                    'osVersion'         => '13',
                    'hl'                => 'fr',
                    'timeZone'          => 'UTC'
                ]
            ],
            [
                'nom'    => 'IOS',
                'id'     => '5',
                'ver'    => '19.09.3',
                'agent'  => 'com.google.ios.youtube/19.09.3 (iPhone16,2; U; CPU iOS 17_4_1 like Mac OS X;)',
                'contexte' => [
                    'clientName'    => 'IOS',
                    'clientVersion' => '19.09.3',
                    'deviceMake'    => 'Apple',
                    'deviceModel'   => 'iPhone16,2',
                    'userAgent'     => 'com.google.ios.youtube/19.09.3 (iPhone16,2; U; CPU iOS 17_4_1 like Mac OS X;)',
                    'osName'        => 'iOS',
                    'osVersion'     => '17.4.1',
                    'hl'            => 'fr',
                    'timeZone'      => 'UTC'
                ]
            ]
        ];

        foreach ($clientsInnerTube as $configurationClient) {
            $corpsPayload = json_encode([
                'context' => ['client' => $configurationClient['contexte']],
                'videoId' => $identifiantVideo,
                'contentCheckOk' => true,
                'racyCheckOk' => true
            ]);

            $urlApi = 'https://www.youtube.com/youtubei/v1/player?prettyPrint=false';
            $enTetesReq = [
                'Content-Type: application/json',
                'X-Youtube-Client-Name: ' . $configurationClient['id'],
                'X-Youtube-Client-Version: ' . $configurationClient['ver'],
                'Origin: https://www.youtube.com',
                'Referer: https://www.youtube.com/watch?v=' . $identifiantVideo
            ];

            $reponseBrute = ClientHttp::effectuerRequetePost(
                $urlApi,
                $corpsPayload,
                $configurationClient['agent'],
                $enTetesReq,
                $fichierCookies
            );

            if ($reponseBrute) {
                $donneesDecodees = json_decode($reponseBrute, true);
                if ($donneesDecodees && !empty($donneesDecodees['streamingData'])) {
                    return $donneesDecodees;
                }
            }
        }

        return null;
    }

    /**
     * Génère la liste des miniatures standard d'une vidéo YouTube.
     *
     * @param string $identifiantVideo
     * @return array
     */
    public static function collecterTableauMiniatures(string $identifiantVideo): array
    {
        $listeTailles = [
            'maxresdefault' => [1280, 720],
            'sddefault'     => [640,  480],
            'hqdefault'     => [480,  360],
            'mqdefault'     => [320,  180],
            'default'       => [120,   90],
        ];

        $resultatMiniatures = [];
        foreach ($listeTailles as $libelleTaille => [$largeur, $hauteur]) {
            $resultatMiniatures[] = [
                'url'     => "https://i.ytimg.com/vi/{$identifiantVideo}/{$libelleTaille}.jpg",
                'largeur' => $largeur,
                'hauteur' => $hauteur,
                'qualite' => $libelleTaille
            ];
        }

        return $resultatMiniatures;
    }
}
