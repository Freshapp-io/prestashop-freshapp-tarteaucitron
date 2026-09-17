<?php
/**
 * FreshApp Tarteaucitron.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   Proprietary - see LICENSE file
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.5.0 — compatibilité étendue de PrestaShop 1.7 à 9 (décodage du code des services identique sur toutes les versions de PHP).
 *
 * Rien n'est à migrer en base. Ce fichier existe pour que PrestaShop reconnaisse
 * la montée de version et enregistre le nouveau numéro au lieu de garder l'ancien.
 */
function upgrade_module_1_5_0($module): bool
{
    return true;
}
