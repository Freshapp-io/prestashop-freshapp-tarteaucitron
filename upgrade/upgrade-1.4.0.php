<?php
/**
 * FreshApp Tarteaucitron.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   GPL-3.0-or-later
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Deux correctifs, tous deux nécessaires pour que la détection automatique bloque
 * réellement les iframes tierces :
 *
 * 1. Le loader était injecté en bas de page. La documentation impose « immediately
 *    after the opening <head> tag » : chargé en fin de document, le script s'exécute
 *    quand les iframes YouTube ont déjà été parsées et requêtées.
 *
 * 2. Le CDN répond HTTP 200 avec le corps console.error('Invalid tarteaucitron.io
 *    licence') quand la licence ne couvre pas le domaine envoyé. Ce corps était accepté,
 *    mis en cache et servi comme loader pendant tout le TTL : aucun gestionnaire de
 *    consentement n'était chargé sur le site.
 *
 * On purge donc les caches empoisonnés pour forcer un nouveau téléchargement, désormais
 * validé.
 */
function upgrade_module_1_4_0(Module $module): bool
{
    Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');

    $cachePath = _PS_MODULE_DIR_ . $module->name . '/views/js/tac-loader.cache.js';

    // 200 octets : la réponse d'erreur de licence en fait 50, le loader réel plusieurs kilos
    if (file_exists($cachePath) && filesize($cachePath) < 200) {
        @unlink($cachePath);
    }

    // Force un re-téléchargement à la prochaine visite du front, quel que soit l'état
    // du cache : la version précédente a pu enregistrer une réponse invalide.
    Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0);

    return true;
}
