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
 * 1.5.5 — l'erreur du dernier téléchargement restait affichée après l'effacement du tag ou
 * l'arrêt du cache local. Celle qui traîne est effacée ici ; elle sera réécrite au prochain
 * téléchargement s'il échoue.
 */
function upgrade_module_1_5_5($module): bool
{
    Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');

    return true;
}
