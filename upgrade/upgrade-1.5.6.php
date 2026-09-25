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
 * 1.5.6 — retrait d'un gabarit orphelin (views/templates/hook/footer-javascript.tpl),
 * jamais appelé par aucun contrôleur, relevé par le validateur Addons. Rien à migrer.
 */
function upgrade_module_1_5_6($module): bool
{
    return true;
}
