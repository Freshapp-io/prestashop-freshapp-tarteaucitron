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
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_1_0(Module $module): bool
{
    Configuration::updateValue('FA_TAC_LOCAL_ENABLED', 0);
    Configuration::updateValue('FA_TAC_LOCAL_TTL', 7);
    Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0);
    Configuration::updateValue('FA_TAC_LOCAL_UUID', '');

    return true;
}
