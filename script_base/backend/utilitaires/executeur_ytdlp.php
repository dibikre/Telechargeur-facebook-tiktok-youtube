<?php

namespace Backend\Utilitaires;

/**
 * Service d'exécution des commandes système pour yt-dlp.
 */
class ExecuteurYtdlp
{
    /**
     * Extrait le JSON de métadonnées d'une URL avec yt-dlp.
     *
     * @param string $urlVideo
     * @param string|null $fichierCookies
     * @return array|null
     */
    public static function extraireFormationsJson(string $urlVideo, ?string $fichierCookies = null): ?array
    {
        $optionsCommande = '--dump-json --no-playlist --no-warnings --no-check-certificate';
        if ($fichierCookies && file_exists($fichierCookies)) {
            $optionsCommande .= ' --cookies ' . escapeshellarg($fichierCookies);
        }

        $commandeExecutee = 'yt-dlp ' . $optionsCommande . ' ' . escapeshellarg($urlVideo) . ' 2>&1';
        $chaineSortie     = shell_exec($commandeExecutee);

        if (!$chaineSortie) {
            return null;
        }

        $lignesSortie = explode("\n", trim($chaineSortie));
        foreach ($lignesSortie as $ligneCourante) {
            $ligneCourante = trim($ligneCourante);
            if (!$ligneCourante || $ligneCourante[0] !== '{') {
                continue;
            }

            $donneesDecodees = json_decode($ligneCourante, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($donneesDecodees['id'])) {
                return $donneesDecodees;
            }
        }

        // Tentative de parsing global si pas de ligne unique
        $debutJson = strpos($chaineSortie, '{');
        if ($debutJson !== false) {
            $donneesDecodees = json_decode(substr($chaineSortie, $debutJson), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($donneesDecodees['id'])) {
                return $donneesDecodees;
            }
        }

        return null;
    }
}
