<?php
/**
 * FreshApp Tarteaucitron.
 *
 * @author    FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license   proprietary
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class Freshapptarteaucitron extends Module
{
    /**
     * Entrée de menu back-office.
     *
     * Visible par défaut. Elle pointe vers le même écran que « Configurer »
     * (le contrôleur ne fait que rappeler getContent), et reste débrayable via
     * la bascule en bas de cet écran.
     */
    public $tabs = [
        [
            'class_name' => 'AdminFreshapptarteaucitron',
            'name' => 'FS Tarteaucitron',
            'parent_class_name' => 'AdminParentModulesSf',
            'visible' => true,
            'wording' => 'FS Tarteaucitron',
            'wording_domain' => 'Modules.Freshapptarteaucitron.Admin',
        ],
    ];

    /** Boutique de démonstration : constante _FA_DEMO_MODE_ définie par l'instance. */
    public static function isDemoMode(): bool
    {
        return defined('_FA_DEMO_MODE_') && (bool) constant('_FA_DEMO_MODE_');
    }

    public function __construct()
    {
        $this->name = 'freshapptarteaucitron';
        $this->tab = 'front_office_features';
        $this->version = '1.5.6';
        $this->author = 'FreshApp.io';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('FreshApp Tarteaucitron', [], 'Modules.Freshapptarteaucitron.Admin');
        $this->description = $this->trans('GDPR friendly cookie manager', [], 'Modules.Freshapptarteaucitron.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => '9.99.99'];
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerSetMedia')
            && Configuration::updateValue('FA_TAC_UUID', '')
            && Configuration::updateValue('FA_TAC_DOMAIN', '')
            && Configuration::updateValue('FA_TAC_JSCODE', '')
            && Configuration::updateValue('FA_TAC_LOCAL_ENABLED', 0)
            && Configuration::updateValue('FA_TAC_LOCAL_TTL', 7)
            && Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0)
            && Configuration::updateValue('FA_TAC_LOCAL_UUID', '')
            && Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');
    }

    public function uninstall(): bool
    {
        if (file_exists($this->getCustomServicesJsPath())) {
            @unlink($this->getCustomServicesJsPath());
        }

        return parent::uninstall()
            && Configuration::deleteByName('FA_TAC_UUID')
            && Configuration::deleteByName('FA_TAC_DOMAIN')
            && Configuration::deleteByName('FA_TAC_JSCODE')
            && Configuration::deleteByName('FA_TAC_LOCAL_ENABLED')
            && Configuration::deleteByName('FA_TAC_LOCAL_TTL')
            && Configuration::deleteByName('FA_TAC_LOCAL_LAST_DL')
            && Configuration::deleteByName('FA_TAC_LOCAL_UUID')
            && Configuration::deleteByName('FA_TAC_LOCAL_ERROR');
    }

    // -------------------------------------------------------------------------
    // Back-office
    // -------------------------------------------------------------------------

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitTacBoMenu') && !Freshapptarteaucitron::isDemoMode()) {
            $idTab = (int) Db::getInstance()->getValue('SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminFreshapptarteaucitron"');
            if ($idTab) {
                $onglet = new Tab($idTab);
                $onglet->active = (bool) Tools::getValue('tac_bo_menu');
                $onglet->save();
            }
        }

        if (Tools::isSubmit('uwtac_force_reload')) {
            if (self::isDemoMode()) {
                // Boutique de démonstration : pas de téléchargement déclenché par un visiteur.
                $output .= $this->displayError($this->l('Désactivé en mode démonstration.'));
            } elseif ($this->downloadTacLoader()) {
                $output .= $this->displayConfirmation($this->l('load.js re-téléchargé avec succès.'));
            } else {
                $output .= $this->displayError(
                    $this->l('Échec du téléchargement de load.js.')
                    . ('' !== $this->downloadError ? ' (' . htmlspecialchars($this->downloadError, ENT_QUOTES) . ')' : ''),
                );
            }
        }

        if (Tools::isSubmit('uwtac_cache_submit')) {
            $localActif = (int) Tools::getValue('FA_TAC_LOCAL_ENABLED');
            Configuration::updateValue('FA_TAC_LOCAL_ENABLED', $localActif);
            Configuration::updateValue('FA_TAC_LOCAL_TTL', max(1, (int) Tools::getValue('FA_TAC_LOCAL_TTL')));
            if (!$localActif) {
                // L'erreur ne porte que sur le cache local : sans lui, elle n'a plus d'objet.
                Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');
            }
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        if (Tools::isSubmit('uwtac_submit')) {
            $result = $this->postProcess();
            foreach ($result['errors'] as $error) {
                $output .= $this->displayError($error);
            }
            foreach ($result['infos'] as $info) {
                $output .= $this->displayConfirmation($info);
            }
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        // Calculée après les traitements ci-dessus : un re-téléchargement réussi efface l'erreur.
        // Sans tag d'installation, il n'y a rien à signaler : le formulaire dit déjà quoi faire.
        $lastError = '' !== (string) Configuration::get('FA_TAC_UUID')
            ? (string) Configuration::get('FA_TAC_LOCAL_ERROR')
            : '';
        if ('' !== $lastError) {
            $this->context->smarty->assign('fa_tac_last_error', $lastError);
            $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/loader-error.tpl');
        }

        if (self::isDemoMode()) {
            $output .= $this->display(__FILE__, 'views/templates/admin/demo-notice.tpl');
        }

        $output .= $this->renderBasculeMenu();

        $this->context->smarty->assign('module_dir', $this->_path);
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        // Ordre de configuration : tag d'installation, paramètres, puis cache local
        $output .= $this->renderForm();
        $output .= $this->renderCacheStatus();

        return $output;
    }

    /**
     * Bascule d'affichage de l'entrée de menu back-office.
     *
     * L'onglet reste joignable par « Configurer » quel que soit son état : la
     * bascule ne fait que l'ajouter ou le retirer de l'arbre de navigation.
     */
    private function renderBasculeMenu(): string
    {
        $idTab = (int) Db::getInstance()->getValue('SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminFreshapptarteaucitron"');

        $this->context->smarty->assign([
            'fa_tac_menu_actif' => $idTab && (new Tab($idTab))->active,
            'fa_tac_label_menu' => $this->l('Afficher dans le menu du back-office'),
            'fa_tac_oui' => $this->l('Oui'),
            'fa_tac_non' => $this->l('Non'),
        ]);
        // Démonstration : l'interrupteur de menu est grisé (refusé aussi côté serveur).
        $this->context->smarty->assign('fa_menu_demo', Freshapptarteaucitron::isDemoMode());

        return $this->display(__FILE__, 'views/templates/admin/bascule-menu.tpl');
    }

    protected function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        // classe CSS de la balise <form> : pleine largeur, voir views/templates/admin/cache-status.tpl
        $helper->name_controller = 'fa-tac-form';
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->submit_action = 'uwtac_submit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            // Aide du champ « Ajouter des services » : views/templates/admin/_configure/helpers/form/form.tpl
            'fa_tac_services_url' => 'fr' === $this->context->language->iso_code
                ? 'https://tarteaucitron.io/installation-gratuite-open-source/#search-service'
                : 'https://tarteaucitron.io/en/free-installation-open-source/#search-service',
        ];

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Deux panneaux dans un même formulaire : le tag d'installation, puis les paramètres.
     * Icônes Material Icons, grille 3/9 et aides : _configure/helpers/form/form.tpl.
     */
    protected function getConfigForm(): array
    {
        $submit = ['title' => $this->l('Enregistrer'), 'name' => 'uwtac_submit'];

        return [
            [
                'form' => [
                    'legend' => ['title' => $this->l('Tag d\'installation tarteaucitron.io'), 'icon' => 'code'],
                    'input' => [
                        [
                            // Non enregistré : l'UUID et le domaine en sont extraits (voir postProcess)
                            'type' => 'textarea',
                            'rows' => 3,
                            'name' => 'FA_TAC_TAG',
                            'label' => $this->l('Tag d\'installation'),
                        ],
                    ],
                    'submit' => $submit,
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->l('Paramètres'), 'icon' => 'settings'],
                    'input' => [
                        [
                            'type' => 'text',
                            'name' => 'FA_TAC_UUID',
                            'label' => $this->trans('API ID', [], 'Modules.Freshapptarteaucitron.Admin'),
                            'col' => 9,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'FA_TAC_DOMAIN',
                            'label' => $this->l('Domaine(s)'),
                            'desc' => $this->l('Laisser vide pour utiliser le domaine courant automatiquement. Format multi-domaines : www.site.fr__https://recette.site.fr__https://staging.site.fr'),
                            'col' => 9,
                        ],
                        [
                            'type' => 'textarea',
                            'rows' => 4,
                            'name' => 'FA_TAC_JSCODE',
                            'label' => $this->trans('Add services (JS Code)', [], 'Modules.Freshapptarteaucitron.Admin'),
                            'col' => 9,
                        ],
                    ],
                    'submit' => $submit,
                ],
            ],
        ];
    }

    protected function getConfigFormValues(): array
    {
        return [
            'FA_TAC_TAG' => '',
            'FA_TAC_UUID' => Configuration::get('FA_TAC_UUID', true),
            'FA_TAC_DOMAIN' => Configuration::get('FA_TAC_DOMAIN', true),
            'FA_TAC_JSCODE' => Configuration::get('FA_TAC_JSCODE', true),
        ];
    }

    /**
     * @return array{errors: string[], infos: string[]}
     */
    protected function postProcess(): array
    {
        $result = ['errors' => [], 'infos' => []];
        $previousUuid = (string) Configuration::get('FA_TAC_UUID');

        $jscode = trim((string) Tools::getValue('FA_TAC_JSCODE'));
        $uuid = trim((string) Tools::getValue('FA_TAC_UUID'));
        $domain = trim((string) Tools::getValue('FA_TAC_DOMAIN'));

        // Le tag complet peut être collé dans son champ dédié, ou par erreur dans le champ API ID
        $tag = trim((string) Tools::getValue('FA_TAC_TAG'));
        $tagInUuidField = '' === $tag && false !== stripos($uuid, 'uuid=');
        if ($tagInUuidField) {
            $tag = $uuid;
        }

        if ('' !== $tag) {
            $parsed = $this->parseInstallTag($tag);
            if (null === $parsed) {
                $result['errors'][] = $this->l('Tag d\'installation non reconnu : collez le bloc complet fourni par tarteaucitron.io, qui contient load.js ainsi que les paramètres domain= et uuid=.');
                if ($tagInUuidField) {
                    $uuid = $previousUuid;
                }
            } else {
                $uuid = $parsed['uuid'];
                $result['infos'][] = sprintf($this->l('Identifiant (UUID) extrait du tag d\'installation : %s'), $uuid);

                $shopDomain = strtolower((string) Tools::getShopDomainSsl());
                $tagDomain = strtolower($parsed['domain']);
                if ('' === $domain && '' !== $tagDomain && $tagDomain !== $shopDomain) {
                    // Le tag vise un autre domaine que celui de la boutique : on le reprend,
                    // sinon le CDN répondrait « Invalid tarteaucitron.io tag installation ».
                    $domain = $parsed['domain'];
                    $result['infos'][] = sprintf($this->l('Domaine repris du tag d\'installation : %s'), $domain);
                }
            }
        }

        Configuration::updateValue('FA_TAC_UUID', $uuid);
        Configuration::updateValue('FA_TAC_DOMAIN', $domain);
        Configuration::updateValue('FA_TAC_JSCODE', $jscode);
        $this->writeCustomServicesJs($jscode);

        // UUID changed → invalidate cache to force re-download
        if ((string) Configuration::get('FA_TAC_UUID') !== $previousUuid) {
            Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0);
            // L'erreur du téléchargement précédent portait sur l'ancien tag : elle ne dit
            // plus rien du nouveau, et resterait affichée indéfiniment.
            Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');
        }

        return $result;
    }

    /**
     * Extrait l'UUID et le domaine du tag d'installation fourni par tarteaucitron.io, dont le
     * script pointe vers https://cdntag.tarteaucitron.io/load.js?domain=…&uuid=….
     *
     * @return array{uuid: string, domain: string}|null null si le tag n'est pas reconnu
     */
    private function parseInstallTag(string $tag): ?array
    {
        $tag = html_entity_decode($tag, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!preg_match('#load\.js\?([^"\'\s<>]+)#i', $tag, $match)) {
            return null;
        }
        parse_str($match[1], $query);

        $uuid = trim((string) ($query['uuid'] ?? ''));
        if (!preg_match('/^[a-f0-9]{16,64}$/i', $uuid)) {
            return null;
        }

        return ['uuid' => $uuid, 'domain' => trim((string) ($query['domain'] ?? ''))];
    }

    /**
     * Panneau « Cache local de load.js » : statut, réglages (activation, durée) et re-téléchargement.
     */
    private function renderCacheStatus(): string
    {
        $enabled = (bool) Configuration::get('FA_TAC_LOCAL_ENABLED');
        $lastDl = (int) Configuration::get('FA_TAC_LOCAL_LAST_DL');
        $ttlDays = max(1, (int) Configuration::get('FA_TAC_LOCAL_TTL'));
        $fileExists = file_exists($this->getLocalJsPath());
        $current = $enabled && $this->isLocalJsCurrent();
        $uuidMismatch = (string) Configuration::get('FA_TAC_LOCAL_UUID') !== (string) Configuration::get('FA_TAC_UUID')
            && false !== Configuration::get('FA_TAC_LOCAL_UUID');

        // L'erreur ne porte que sur le cache local d'un tag donné : sans cache local, ou sans
        // tag, elle ne décrit plus l'état du module et ne doit pas s'afficher.
        $lastError = $enabled && '' !== (string) Configuration::get('FA_TAC_UUID')
            ? (string) Configuration::get('FA_TAC_LOCAL_ERROR')
            : '';

        if (!$enabled) {
            $state = 'cdn';
        } elseif ('' !== $lastError) {
            $state = 'error';
        } elseif ($fileExists && !$this->isLocalJsUsable()) {
            $state = 'invalid';
        } elseif (!$fileExists) {
            $state = 'missing';
        } elseif ($uuidMismatch) {
            $state = 'uuid';
        } elseif (!$current) {
            $state = 'expired';
        } else {
            $state = 'active';
        }

        $this->context->smarty->assign([
            'fa_tac_state' => $state,
            'fa_tac_enabled' => $enabled,
            'fa_tac_ttl' => (int) Configuration::get('FA_TAC_LOCAL_TTL') ?: 7,
            'fa_tac_last_error' => $lastError,
            'fa_tac_expiry' => date('d/m/Y à H:i', $lastDl + $ttlDays * 86400),
            'fa_tac_last_download' => $lastDl > 0 ? date('d/m/Y à H:i:s', $lastDl) : '',
            'fa_tac_file_kb' => $fileExists ? round(filesize($this->getLocalJsPath()) / 1024, 1) : null,
            'fa_tac_action_url' => $this->context->link->getAdminLink('AdminModules', true)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/cache-status.tpl');
    }

    // -------------------------------------------------------------------------
    // Cache helpers
    // -------------------------------------------------------------------------

    private function getLocalJsPath(): string
    {
        return _PS_MODULE_DIR_ . $this->name . '/views/js/tac-loader.cache.js';
    }

    /**
     * Path to the generated "Add services" JS file — delivered via registerJavascript()
     * (same CCC pipeline as the loader) instead of an inline hookDisplayFooter template,
     * which was silently never executed on some environments.
     */
    private function getCustomServicesJsPath(): string
    {
        return _PS_MODULE_DIR_ . $this->name . '/views/js/custom-services.cache.js';
    }

    /**
     * Writes FA_TAC_JSCODE to a static local file so it can be registered as a regular
     * front asset. Removes the file when the code is emptied, so no stale services stay
     * registered.
     */
    private function writeCustomServicesJs(string $jscode): bool
    {
        $path = $this->getCustomServicesJsPath();

        if ('' === $jscode) {
            if (file_exists($path)) {
                @unlink($path);
            }

            return true;
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        return false !== file_put_contents($path, html_entity_decode($jscode, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8'));
    }

    private function isLocalJsCurrent(): bool
    {
        if (!file_exists($this->getLocalJsPath())) {
            return false;
        }
        $lastDl = (int) Configuration::get('FA_TAC_LOCAL_LAST_DL');
        $ttlDays = max(1, (int) Configuration::get('FA_TAC_LOCAL_TTL'));
        if (time() > $lastDl + $ttlDays * 86400) {
            return false;
        }
        // UUID changed since last download → must refresh
        $cachedUuid = (string) Configuration::get('FA_TAC_LOCAL_UUID');
        $currentUuid = (string) Configuration::get('FA_TAC_UUID');
        if ($cachedUuid !== $currentUuid) {
            return false;
        }

        return true;
    }

    /**
     * Downloads load.js from the tarteaucitron CDN and saves it locally.
     * Returns true on success, false on any failure.
     * Sets $this->downloadError with a human-readable message on failure.
     */
    private string $downloadError = '';

    private function downloadTacLoader(): bool
    {
        $uuid = (string) Configuration::get('FA_TAC_UUID');
        $url = 'https://cdntag.tarteaucitron.io/load.js?domain=' . rawurlencode($this->getDomainParam()) . '&uuid=' . rawurlencode($uuid);

        $content = $this->fetchUrl($url);
        if (false === $content || '' === $content) {
            return false;
        }

        // Le CDN répond 200 même quand la licence ne couvre pas le domaine : le corps
        // vaut alors console.error('Invalid tarteaucitron.io licence'). Le mettre en
        // cache revient à servir un site sans aucun gestionnaire de consentement.
        if (!$this->isValidLoaderPayload($content)) {
            $this->downloadError = $this->describeInvalidPayload($content) . ' (URL : ' . $url . ')';
            Configuration::updateValue('FA_TAC_LOCAL_ERROR', $this->downloadError);

            // On supprime un éventuel cache empoisonné pour repasser en CDN
            if (file_exists($this->getLocalJsPath())) {
                @unlink($this->getLocalJsPath());
            }
            Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0);

            return false;
        }

        $dir = dirname($this->getLocalJsPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (false === file_put_contents($this->getLocalJsPath(), $content)) {
            $this->downloadError = 'Impossible d\'écrire le fichier : ' . $this->getLocalJsPath()
                . ' — vérifiez les permissions du dossier views/js/';

            return false;
        }

        Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', time());
        Configuration::updateValue('FA_TAC_LOCAL_UUID', $uuid);
        Configuration::updateValue('FA_TAC_LOCAL_ERROR', '');

        return true;
    }

    /**
     * Taille en dessous de laquelle un corps de réponse ne peut pas être le loader.
     * La réponse d'erreur de licence fait 50 octets, le loader réel plusieurs kilos.
     */
    private const LOADER_MIN_BYTES = 200;

    /**
     * Le corps téléchargé ressemble-t-il au loader tarteaucitron ?
     */
    private function isValidLoaderPayload(string $content): bool
    {
        if (strlen($content) < self::LOADER_MIN_BYTES) {
            return false;
        }

        if (false !== stripos($content, 'invalid tarteaucitron.io licence')) {
            return false;
        }

        return false !== stripos($content, 'tarteaucitron');
    }

    /**
     * Message d'erreur exploitable en back-office pour un corps de réponse rejeté.
     */
    private function describeInvalidPayload(string $content): string
    {
        if (false !== stripos($content, 'invalid tarteaucitron.io licence')) {
            return 'Licence tarteaucitron.io invalide pour le domaine « ' . $this->getDomainParam()
                . ' ». Vérifiez que ce domaine exact est déclaré sur votre compte tarteaucitron.io '
                . 'et que l\'API ID est correct, ou renseignez le champ Domaine(s).';
        }

        return 'Réponse inattendue du CDN (' . strlen($content) . ' octets) : '
            . substr(preg_replace('/\s+/', ' ', $content) ?? '', 0, 120);
    }

    /**
     * Le fichier de cache est-il exploitable ?
     *
     * Contrôle volontairement peu coûteux (un stat, pas de lecture) : la validation
     * complète a lieu au téléchargement. Ce garde-fou rattrape les caches empoisonnés
     * par une version antérieure du module.
     */
    private function isLocalJsUsable(): bool
    {
        $path = $this->getLocalJsPath();

        return file_exists($path) && filesize($path) >= self::LOADER_MIN_BYTES;
    }

    /**
     * HTTP GET via cURL (preferred) with fallback to file_get_contents.
     * Populates $this->downloadError on failure.
     *
     * @return string|false
     */
    private function fetchUrl(string $url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'PrestaShop/' . _PS_VERSION_ . ' freshapptarteaucitron/' . $this->version,
            ]);
            $content = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if (false === $content || '' !== $curlErr) {
                $this->downloadError = 'cURL error : ' . $curlErr . ' (URL : ' . $url . ')';

                return false;
            }
            if (200 !== $httpCode) {
                $this->downloadError = 'HTTP ' . $httpCode . ' reçu depuis ' . $url;

                return false;
            }
            if ('' === $content || '0' === $content) {
                $this->downloadError = 'Réponse vide depuis ' . $url . ' (vérifiez l\'UUID)';

                return false;
            }

            return (string) $content;
        }

        // Fallback: file_get_contents
        if (!ini_get('allow_url_fopen')) {
            $this->downloadError = 'cURL indisponible et allow_url_fopen est désactivé — le serveur ne peut pas effectuer de requêtes HTTP sortantes';

            return false;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'PrestaShop/' . _PS_VERSION_ . ' freshapptarteaucitron/' . $this->version,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $content = @file_get_contents($url, false, $ctx);
        if (false === $content || '' === $content) {
            $this->downloadError = 'file_get_contents a échoué pour ' . $url;

            return false;
        }

        return $content;
    }

    // -------------------------------------------------------------------------
    // Front-office hooks
    // -------------------------------------------------------------------------

    /**
     * Returns the domain parameter value for the tarteaucitron CDN URL.
     * Uses the custom FA_TAC_DOMAIN config if set, otherwise falls back to the current server name.
     */
    private function getDomainParam(): string
    {
        $custom = (string) Configuration::get('FA_TAC_DOMAIN');

        if ('' !== $custom) {
            return $custom;
        }

        // Le domaine déclaré de la boutique, pas $_SERVER['SERVER_NAME'] : derrière un
        // proxy, un CDN ou en ligne de commande, SERVER_NAME vaut l'hôte interne et le
        // CDN répond alors « Invalid tarteaucitron.io licence ».
        $shopDomain = (string) Tools::getShopDomainSsl();

        return '' !== $shopDomain ? $shopDomain : (string) ($_SERVER['SERVER_NAME'] ?? '');
    }

    public function hookDisplayHeader(): string
    {
        return $this->display(__FILE__, 'views/templates/hook/header.tpl');
    }

    /**
     * Le loader doit être le tout premier script de la page.
     *
     * https://tarteaucitron.io/en/free-installation-open-source/ : « Place this code
     * immediately after the opening <head> tag ». Ce n'est pas une préconisation de
     * performance : la détection automatique neutralise les iframes tierces (YouTube,
     * Vimeo…) avant que le navigateur ne les charge. Chargé en bas de page, le script
     * s'exécute une fois les iframes déjà parsées et déjà requêtées — les cookies sont
     * posés et aucun consentement n'est demandé.
     */
    private const JS_POSITION = 'head';

    /** Priorité basse = injecté en premier (DEFAULT_PRIORITY vaut 50). */
    private const JS_PRIORITY_LOADER = 1;

    /** Les services déclarés doivent s'exécuter après le loader, jamais avant. */
    private const JS_PRIORITY_SERVICES = 2;

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->registerLocalLoader()) {
            $this->registerCustomServicesJs();

            return;
        }

        // CDN fallback : mode local désactivé, cache absent ou cache invalide
        $this->context->controller->registerJavascript(
            $this->name . '-loader',
            'https://cdntag.tarteaucitron.io/load.js?domain=' . rawurlencode($this->getDomainParam())
                . '&uuid=' . rawurlencode((string) Configuration::get('FA_TAC_UUID')),
            [
                'server' => 'remote',
                'position' => self::JS_POSITION,
                'priority' => self::JS_PRIORITY_LOADER,
            ],
        );

        $this->registerCustomServicesJs();
    }

    /**
     * Enregistre le loader mis en cache localement.
     *
     * Renvoie false — et laisse donc la main au CDN — si le fichier est absent ou
     * inexploitable. Servir un cache invalide reviendrait à ne charger aucun gestionnaire
     * de consentement : c'est exactement ce que produisait une réponse « Invalid
     * tarteaucitron.io licence » du CDN, mise en cache puis servie pendant tout le TTL.
     */
    private function registerLocalLoader(): bool
    {
        if (!(bool) Configuration::get('FA_TAC_LOCAL_ENABLED')) {
            return false;
        }

        // Refresh cache if stale (happens at most once per TTL period)
        if (!$this->isLocalJsCurrent()) {
            $this->downloadTacLoader();
        }

        if (!$this->isLocalJsUsable()) {
            return false;
        }

        $this->context->controller->registerJavascript(
            $this->name . '-loader',
            'modules/' . $this->name . '/views/js/tac-loader.cache.js',
            ['position' => self::JS_POSITION, 'priority' => self::JS_PRIORITY_LOADER],
        );

        return true;
    }

    /**
     * Registers the "Add services" JS file, self-healing (regenerating from config) if
     * it's missing — covers upgrades from versions where this file didn't exist yet.
     */
    private function registerCustomServicesJs(): void
    {
        $jscode = (string) Configuration::get('FA_TAC_JSCODE');
        if ('' === $jscode) {
            return;
        }

        $path = $this->getCustomServicesJsPath();
        if (!file_exists($path)) {
            $this->writeCustomServicesJs($jscode);
        }

        if (file_exists($path)) {
            $this->context->controller->registerJavascript(
                $this->name . '-services',
                'modules/' . $this->name . '/views/js/custom-services.cache.js',
                ['position' => self::JS_POSITION, 'priority' => self::JS_PRIORITY_SERVICES],
            );
        }
    }
}
