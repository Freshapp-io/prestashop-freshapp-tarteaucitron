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
 * 1.5.7 — rangement demandé par le validateur Addons : échappement explicite dans le gabarit
 * du formulaire, index.php de protection dans controllers/, en-têtes de licence PrestaShop
 * périmés retirés, types explicites côté contrôleur. Rien à migrer.
 */
function upgrade_module_1_5_7(Module $module): bool
{
    return true;
}
