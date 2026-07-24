<?php

namespace Backend\Interfaces;

/**
 * Interface pour les traitements de récupération de données vidéo.
 */
interface InterfaceRecuperateur
{
    /**
     * Extrait les métadonnées et flux d'une vidéo à partir de son URL.
     *
     * @param string $urlVideo
     * @return array
     */
    public function extraire(string $urlVideo): array;

    /**
     * Indique si l'URL est gérée par ce récupérateur.
     *
     * @param string $urlVideo
     * @return bool
     */
    public function correspondA(string $urlVideo): bool;
}
