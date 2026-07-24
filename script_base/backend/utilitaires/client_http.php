<?php

namespace Backend\Utilitaires;

/**
 * Client HTTP cURL pour le scraping et les requêtes API.
 */
class ClientHttp
{
    /**
     * Effectue une requête HTTP GET avec cURL.
     *
     * @param string $urlCible
     * @param string|null $fichierCookies
     * @param string|null $agentUtilisateur
     * @param array $enTetesAdditionnels
     * @return array
     */
    public static function effectuerRequeteGet(
        string $urlCible,
        ?string $fichierCookies = null,
        ?string $agentUtilisateur = null,
        array $enTetesAdditionnels = []
    ): array {
        $agentUtilisateur = $agentUtilisateur ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';

        $sessionCurl = curl_init();
        $optionsCurl = [
            CURLOPT_URL            => $urlCible,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => $agentUtilisateur,
            CURLOPT_ENCODING       => 'gzip, deflate, br',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => array_merge([
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Connection: keep-alive',
            ], $enTetesAdditionnels),
        ];

        if ($fichierCookies) {
            $optionsCurl[CURLOPT_COOKIEFILE] = $fichierCookies;
            $optionsCurl[CURLOPT_COOKIEJAR]  = $fichierCookies;
        }

        curl_setopt_array($sessionCurl, $optionsCurl);
        $contenuReponse = curl_exec($sessionCurl);
        $urlFinale      = curl_getinfo($sessionCurl, CURLINFO_EFFECTIVE_URL);
        $codeErreur     = curl_errno($sessionCurl);
        $messageErreur  = curl_error($sessionCurl);
        curl_close($sessionCurl);

        return [
            'html'   => is_string($contenuReponse) ? $contenuReponse : '',
            'url'    => $urlFinale ?: $urlCible,
            'code'   => $codeErreur,
            'erreur' => $messageErreur,
        ];
    }

    /**
     * Effectue une requête HTTP POST avec cURL.
     *
     * @param string $urlCible
     * @param string $corpsRequete
     * @param string $agentUtilisateur
     * @param array $enTetesAdditionnels
     * @param string|null $fichierCookies
     * @return string|null
     */
    public static function effectuerRequetePost(
        string $urlCible,
        string $corpsRequete,
        string $agentUtilisateur,
        array $enTetesAdditionnels,
        ?string $fichierCookies = null
    ): ?string {
        $sessionCurl = curl_init();
        $optionsCurl = [
            CURLOPT_URL            => $urlCible,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $corpsRequete,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => $agentUtilisateur,
            CURLOPT_HTTPHEADER     => $enTetesAdditionnels,
        ];

        if ($fichierCookies) {
            $optionsCurl[CURLOPT_COOKIEFILE] = $fichierCookies;
        }

        curl_setopt_array($sessionCurl, $optionsCurl);
        $contenuReponse = curl_exec($sessionCurl);
        curl_close($sessionCurl);

        return is_string($contenuReponse) ? $contenuReponse : null;
    }
}
