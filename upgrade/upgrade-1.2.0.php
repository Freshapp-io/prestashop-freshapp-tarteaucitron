<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0(Module $module): bool
{
    Configuration::updateValue('FA_TAC_DOMAIN', '');
    $module->registerHook('displayHeader');

    return true;
}
