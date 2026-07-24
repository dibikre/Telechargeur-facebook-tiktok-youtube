<?php

namespace Backend\Utilitaires;

/**
 * Service de formatage et d'envoi des réponses HTTP JSON.
 */
class ReponseJson
{
    /**
     * Envoie une réponse JSON de succès.
     *
     * @param array $donnees
     * @param int $codeStatut
     * @return void
     */
    public static function envoyerSucces(array $donnees, int $codeStatut = 200): void
    {
        http_response_code($codeStatut);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        echo json_encode([
            'success' => true,
            'data'    => $donnees
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envoie une réponse JSON d'erreur.
     *
     * @param string $messageErreur
     * @param int $codeStatut
     * @return void
     */
    public static function envoyerErreur(string $messageErreur, int $codeStatut = 400): void
    {
        http_response_code($codeStatut);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        echo json_encode([
            'success' => false,
            'error'   => $messageErreur
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Gère les requêtes préliminaires CORS OPTIONS.
     *
     * @return void
     */
    public static function envoyerOptions(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit;
    }
}
