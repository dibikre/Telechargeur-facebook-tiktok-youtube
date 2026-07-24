<?php

namespace Backend\Traitements;

use Backend\Interfaces\InterfaceRecuperateur;

/**
 * Classe abstraite de base pour les récupérateurs de plateformes.
 */
abstract class RecuperateurAbstrait implements InterfaceRecuperateur
{
    /**
     * Sauvegarde les données brutes sous forme de fichier JSON miroir.
     *
     * @param string|null $identifiantVideo
     * @param array $donnees
     * @return string|null
     */
    protected function sauvegarderMiroirJson(?string $identifiantVideo, array $donnees): ?string
    {
        if (!$identifiantVideo) {
            return null;
        }

        $repertoireBackend = __DIR__ . '/../stockage';
        if (!is_dir($repertoireBackend)) {
            mkdir($repertoireBackend, 0777, true);
        }

        $cheminFichier = $repertoireBackend . '/' . $identifiantVideo . '.json';
        file_put_contents(
            $cheminFichier,
            json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $identifiantVideo . '.json';
    }

    /**
     * Formatage uniforme de la date de publication au format JJ/MM/AAAA.
     *
     * @param string|null $chaineDate
     * @return string|null
     */
    protected function formaterDatePublication(?string $chaineDate): ?string
    {
        if ($chaineDate && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $chaineDate, $correspondances)) {
            return $correspondances[3] . '/' . $correspondances[2] . '/' . $correspondances[1];
        }
        return null;
    }

    /**
     * Traite les sous-titres et légendes retournés par yt-dlp.
     *
     * @param array $donneesYtdlp
     * @return array
     */
    protected function extraireCaptions(array $donneesYtdlp): array
    {
        $listeCaptions = [];
        $sourcesCaptions = array_merge(
            array_map(
                fn($cle, $val) => [$cle, $val, 'auto'],
                array_keys($donneesYtdlp['automatic_captions'] ?? []),
                array_values($donneesYtdlp['automatic_captions'] ?? [])
            ),
            array_map(
                fn($cle, $val) => [$cle, $val, 'manual'],
                array_keys($donneesYtdlp['subtitles'] ?? []),
                array_values($donneesYtdlp['subtitles'] ?? [])
            )
        );

        foreach ($sourcesCaptions as [$langue, $pistes, $typeCaption]) {
            if (!is_array($pistes)) {
                continue;
            }
            foreach ($pistes as $pisteCourante) {
                if (isset($pisteCourante['url']) && $pisteCourante['url']) {
                    $listeCaptions[] = [
                        'langue' => $langue,
                        'type'   => $typeCaption,
                        'ext'    => $pisteCourante['ext'] ?? 'vtt',
                        'nom'    => $pisteCourante['name'] ?? $langue,
                        'url'    => $pisteCourante['url'],
                    ];
                }
            }
        }

        return $listeCaptions;
    }
}
