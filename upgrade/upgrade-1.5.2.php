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
 * 1.5.2 — entrée de menu déplacée dans Modules et renommée « FS Tarteaucitron ».
 */
function upgrade_module_1_5_2($module): bool
{
    $idTab = (int) Tab::getIdFromClassName('AdminFreshapptarteaucitron');
    if ($idTab) {
        $tab = new Tab($idTab);
        $idParent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        if ($idParent && (int) $tab->id_parent !== $idParent) {
            $tab->id_parent = $idParent;
            $tab->position = Tab::getNewLastPosition($idParent);
        }
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'FS Tarteaucitron';
        }
        if (property_exists($tab, 'wording') && $tab->wording) {
            $tab->wording = 'FS Tarteaucitron';
        }
        $tab->save();
    }

    return true;
}
