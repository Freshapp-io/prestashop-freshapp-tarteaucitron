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
 * "Add services (JS Code)" was delivered via hookDisplayFooter + a Smarty template,
 * silently never executing on some environments. Replaced with a static JS file
 * registered via registerJavascript() (same reliable pipeline as the load.js loader).
 * The file itself is (re)generated lazily on the next front hit — see
 * freshapptarteaucitron::registerCustomServicesJs().
 */
function upgrade_module_1_3_0(Module $module): bool
{
    $module->unregisterHook('displayFooter');

    return true;
}
