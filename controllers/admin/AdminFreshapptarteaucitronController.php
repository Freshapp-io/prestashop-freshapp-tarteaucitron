<?php
/**
 * FreshApp Tarteaucitron
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   proprietary
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Écran atteint depuis l'entrée de menu.
 *
 * Il ne réimplémente rien : l'interface vit dans getContent(), et « Configurer »
 * comme l'entrée de menu affichent donc strictement la même chose.
 */
class AdminFreshapptarteaucitronController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
    }

    public function renderView(): string
    {
        return $this->module->getContent();
    }
}
