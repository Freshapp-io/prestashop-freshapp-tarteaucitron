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
            'name' => 'Tarteaucitron',
            'parent_class_name' => 'AdminParentPreferences',
            'visible' => true,
            'wording' => 'Tarteaucitron',
            'wording_domain' => 'Modules.Freshapptarteaucitron.Admin',
        ],
    ];

    public function __construct()
    {
        $this->name = 'freshapptarteaucitron';
        $this->tab = 'front_office_features';
        $this->version = '1.4.0';
        $this->author = 'FreshApp.io';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('FreshApp Tarteaucitron', [], 'Modules.Freshapptarteaucitron.Admin');
        $this->description = $this->trans('GDPR friendly cookie manager', [], 'Modules.Freshapptarteaucitron.Admin');
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
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

        if (Tools::isSubmit('submitTacBoMenu')) {
            $idTab = (int) Tab::getIdFromClassName('AdminFreshapptarteaucitron');
            if ($idTab) {
                $onglet = new Tab($idTab);
                $onglet->active = Tools::getValue('tac_bo_menu') ? 1 : 0;
                $onglet->save();
            }
        }

        if (Tools::isSubmit('uwtac_force_reload')) {
            if ($this->downloadTacLoader()) {
                $output .= $this->displayConfirmation($this->l('load.js re-téléchargé avec succès.'));
            } else {
                $output .= $this->displayError(
                    $this->l('Échec du téléchargement de load.js.')
                    . ($this->downloadError !== '' ? '<br><code>' . htmlspecialchars($this->downloadError, ENT_QUOTES) . '</code>' : '')
                );
            }
        }

        if (Tools::isSubmit('uwtac_submit')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        $output .= $this->renderBasculeMenu();

        $this->context->smarty->assign('module_dir', $this->_path);
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $output .= $this->renderCacheStatus();
        $output .= $this->renderForm();

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
        $idTab = (int) Tab::getIdFromClassName('AdminFreshapptarteaucitron');
        $actif = $idTab && (new Tab($idTab))->active;

        return '<div class="panel">'
            . '<form method="post" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin:0">'
            . '<span>' . $this->l('Afficher dans le menu du back-office') . '</span>'
            . '<input type="hidden" name="submitTacBoMenu" value="1">'
            . '<span class="switch prestashop-switch fixed-width-lg">'
            . '<input type="radio" name="tac_bo_menu" id="tac_bo_menu_on" value="1"'
            . ($actif ? ' checked="checked"' : '') . ' onchange="this.form.submit()">'
            . '<label for="tac_bo_menu_on">' . $this->l('Oui') . '</label>'
            . '<input type="radio" name="tac_bo_menu" id="tac_bo_menu_off" value="0"'
            . (!$actif ? ' checked="checked"' : '') . ' onchange="this.form.submit()">'
            . '<label for="tac_bo_menu_off">' . $this->l('Non') . '</label>'
            . '<a class="slide-button btn"></a></span>'
            . '</form></div>';
    }

    protected function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
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
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm(): array
    {
        return [
            'form' => [
                'legend' => ['title' => $this->l('Paramètres'), 'icon' => 'icon-cogs'],
                'tabs' => [
                    'general' => $this->l('Général'),
                    'cache'   => $this->l('Cache local'),
                ],
                'input' => [
                    [
                        'type'  => 'text',
                        'name'  => 'FA_TAC_UUID',
                        'label' => $this->trans('API ID', [], 'Modules.Freshapptarteaucitron.Admin'),
                        'tab'   => 'general',
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'FA_TAC_DOMAIN',
                        'label' => $this->l('Domaine(s)'),
                        'hint'  => $this->l('Laisser vide pour utiliser le domaine courant automatiquement. Format multi-domaines : www.site.fr__https://recette.site.fr__https://staging.site.fr'),
                        'tab'   => 'general',
                    ],
                    [
                        'type'  => 'textarea',
                        'size'  => 3,
                        'name'  => 'FA_TAC_JSCODE',
                        'label' => $this->trans('Add services (JS Code)', [], 'Modules.Freshapptarteaucitron.Admin'),
                        'hint'  => $this->trans(
                            'If you use free installation, read "Step 3: Add services" on https://tarteaucitron.io/en/free-installation-open-source/',
                            [],
                            'Modules.Freshapptarteaucitron.Admin'
                        ),
                        'tab'   => 'general',
                    ],
                    [
                        'type'     => 'switch',
                        'name'     => 'FA_TAC_LOCAL_ENABLED',
                        'label'    => $this->l('Activer le cache local de load.js'),
                        'hint'     => $this->l('Télécharge load.js en local et le sert avec les assets du site (minifié). Fallback CDN automatique si le téléchargement échoue.'),
                        'is_bool'  => true,
                        'values'   => [
                            ['id' => 'local_on',  'value' => 1, 'label' => $this->l('Activé')],
                            ['id' => 'local_off', 'value' => 0, 'label' => $this->l('Désactivé')],
                        ],
                        'tab' => 'cache',
                    ],
                    [
                        'type'   => 'text',
                        'name'   => 'FA_TAC_LOCAL_TTL',
                        'label'  => $this->l('Durée du cache'),
                        'hint'   => $this->l('Nombre de jours avant re-téléchargement automatique depuis le CDN. Minimum 1.'),
                        'suffix' => $this->l('jours'),
                        'class'  => 'fixed-width-sm',
                        'tab'    => 'cache',
                    ],
                ],
                'submit' => ['title' => $this->l('Enregistrer')],
            ],
        ];
    }

    protected function getConfigFormValues(): array
    {
        return [
            'FA_TAC_UUID'          => Configuration::get('FA_TAC_UUID', true),
            'FA_TAC_DOMAIN'        => Configuration::get('FA_TAC_DOMAIN', true),
            'FA_TAC_JSCODE'        => Configuration::get('FA_TAC_JSCODE', true),
            'FA_TAC_LOCAL_ENABLED' => (int) Configuration::get('FA_TAC_LOCAL_ENABLED'),
            'FA_TAC_LOCAL_TTL'     => (int) Configuration::get('FA_TAC_LOCAL_TTL') ?: 7,
        ];
    }

    protected function postProcess(): void
    {
        $previousUuid = (string) Configuration::get('FA_TAC_UUID');

        $jscode = trim((string) Tools::getValue('FA_TAC_JSCODE'));

        Configuration::updateValue('FA_TAC_UUID', trim((string) Tools::getValue('FA_TAC_UUID')));
        Configuration::updateValue('FA_TAC_DOMAIN', trim((string) Tools::getValue('FA_TAC_DOMAIN')));
        Configuration::updateValue('FA_TAC_JSCODE', $jscode);
        Configuration::updateValue('FA_TAC_LOCAL_ENABLED', (int) Tools::getValue('FA_TAC_LOCAL_ENABLED'));
        Configuration::updateValue('FA_TAC_LOCAL_TTL', max(1, (int) Tools::getValue('FA_TAC_LOCAL_TTL')));
        $this->writeCustomServicesJs($jscode);

        // UUID changed → invalidate cache to force re-download
        if ((string) Configuration::get('FA_TAC_UUID') !== $previousUuid) {
            Configuration::updateValue('FA_TAC_LOCAL_LAST_DL', 0);
        }
    }

    /**
     * Panel affiché en BO avec le statut du cache et le bouton force-reload.
     */
    private function renderCacheStatus(): string
    {
        $enabled    = (bool) Configuration::get('FA_TAC_LOCAL_ENABLED');
        $lastDl     = (int) Configuration::get('FA_TAC_LOCAL_LAST_DL');
        $ttlDays    = max(1, (int) Configuration::get('FA_TAC_LOCAL_TTL'));
        $fileExists = file_exists($this->getLocalJsPath());
        $current    = $enabled && $this->isLocalJsCurrent();
        $uuidMismatch = (string) Configuration::get('FA_TAC_LOCAL_UUID') !== (string) Configuration::get('FA_TAC_UUID')
            && Configuration::get('FA_TAC_LOCAL_UUID') !== false;

        $lastError = (string) Configuration::get('FA_TAC_LOCAL_ERROR');

        if ($lastError !== '') {
            $badge = '<span class="badge badge-danger">Loader non chargé</span>';
            $info  = '<p class="text-danger"><strong>Le gestionnaire de consentement n\'est pas actif.</strong><br>'
                . htmlspecialchars($lastError, ENT_QUOTES) . '</p>'
                . '<p>Le site est repassé sur le CDN en attendant. Corrigez le point ci-dessus '
                . 'puis relancez le téléchargement.</p>';
        } elseif (!$enabled) {
            $badge = '<span class="badge badge-default">Mode CDN</span>';
            $info  = '<p class="text-muted">load.js est chargé depuis le CDN tarteaucitron.io à chaque visite.</p>';
        } elseif ($fileExists && !$this->isLocalJsUsable()) {
            $badge = '<span class="badge badge-danger">Cache invalide</span>';
            $info  = '<p class="text-danger">Le fichier en cache est trop petit pour être le loader '
                . '(réponse d\'erreur du CDN mise en cache). Le site est repassé sur le CDN ; '
                . 'relancez le téléchargement pour connaître la cause exacte.</p>';
        } elseif (!$fileExists) {
            $badge = '<span class="badge badge-warning">Non téléchargé</span>';
            $info  = '<p>Le fichier cache n\'existe pas encore. Il sera téléchargé automatiquement lors de la prochaine visite du site front.</p>';
        } elseif ($uuidMismatch) {
            $badge = '<span class="badge badge-warning">UUID modifié</span>';
            $info  = '<p class="text-warning">L\'UUID a changé depuis le dernier téléchargement. Le fichier sera re-téléchargé automatiquement à la prochaine visite.</p>';
        } elseif (!$current) {
            $badge = '<span class="badge badge-warning">Cache expiré</span>';
            $info  = '<p>Le cache a expiré. Il sera re-téléchargé automatiquement à la prochaine visite.</p>';
        } else {
            $expiry = date('d/m/Y à H:i', $lastDl + $ttlDays * 86400);
            $badge  = '<span class="badge badge-success">Cache actif</span>';
            $info   = '<p class="text-success">load.js est servi en local et intégré aux assets du site. Prochain re-téléchargement le <strong>' . $expiry . '</strong>.</p>';
        }

        if ($lastDl > 0) {
            $info .= '<p>Dernier téléchargement&nbsp;: <strong>' . date('d/m/Y à H:i:s', $lastDl) . '</strong></p>';
        }
        if ($fileExists) {
            $kb = round(filesize($this->getLocalJsPath()) / 1024, 1);
            $info .= '<p>Taille du fichier cache&nbsp;: <strong>' . $kb . ' ko</strong></p>';
        }

        $actionUrl = htmlspecialchars(
            $this->context->link->getAdminLink('AdminModules', true)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
            ENT_QUOTES
        );

        $forceBtn = '';
        if ($enabled) {
            $forceBtn = '<form method="post" action="' . $actionUrl . '" style="margin-top:12px">
                <button type="submit" name="uwtac_force_reload" value="1" class="btn btn-default">
                    <i class="icon-refresh"></i>&nbsp;Forcer le re-téléchargement maintenant
                </button>
            </form>';
        }

        return '<div class="panel">
            <div class="panel-heading"><i class="icon-cloud-download"></i> Cache local de load.js &nbsp;' . $badge . '</div>
            <div class="panel-body">' . $info . $forceBtn . '</div>
        </div>';
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

        if ($jscode === '') {
            if (file_exists($path)) {
                @unlink($path);
            }

            return true;
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($path, html_entity_decode($jscode)) !== false;
    }

    private function isLocalJsCurrent(): bool
    {
        if (!file_exists($this->getLocalJsPath())) {
            return false;
        }
        $lastDl  = (int) Configuration::get('FA_TAC_LOCAL_LAST_DL');
        $ttlDays = max(1, (int) Configuration::get('FA_TAC_LOCAL_TTL'));
        if (time() > $lastDl + $ttlDays * 86400) {
            return false;
        }
        // UUID changed since last download → must refresh
        $cachedUuid  = (string) Configuration::get('FA_TAC_LOCAL_UUID');
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
        $url  = 'https://cdntag.tarteaucitron.io/load.js?domain=' . rawurlencode($this->getDomainParam()) . '&uuid=' . rawurlencode($uuid);

        $content = $this->fetchUrl($url);
        if ($content === false || $content === '') {
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

        if (file_put_contents($this->getLocalJsPath(), $content) === false) {
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

        if (stripos($content, 'invalid tarteaucitron.io licence') !== false) {
            return false;
        }

        return stripos($content, 'tarteaucitron') !== false;
    }

    /**
     * Message d'erreur exploitable en back-office pour un corps de réponse rejeté.
     */
    private function describeInvalidPayload(string $content): string
    {
        if (stripos($content, 'invalid tarteaucitron.io licence') !== false) {
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
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT      => 'PrestaShop/' . _PS_VERSION_ . ' freshapptarteaucitron/' . $this->version,
            ]);
            $content  = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($content === false || $curlErr !== '') {
                $this->downloadError = 'cURL error : ' . $curlErr . ' (URL : ' . $url . ')';

                return false;
            }
            if ($httpCode !== 200) {
                $this->downloadError = 'HTTP ' . $httpCode . ' reçu depuis ' . $url;

                return false;
            }
            if ($content === '' || $content === '0') {
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
                'timeout'    => 15,
                'user_agent' => 'PrestaShop/' . _PS_VERSION_ . ' freshapptarteaucitron/' . $this->version,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $content = @file_get_contents($url, false, $ctx);
        if ($content === false || $content === '') {
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

        if ($custom !== '') {
            return $custom;
        }

        // Le domaine déclaré de la boutique, pas $_SERVER['SERVER_NAME'] : derrière un
        // proxy, un CDN ou en ligne de commande, SERVER_NAME vaut l'hôte interne et le
        // CDN répond alors « Invalid tarteaucitron.io licence ».
        $shopDomain = (string) Tools::getShopDomainSsl();

        return $shopDomain !== '' ? $shopDomain : (string) ($_SERVER['SERVER_NAME'] ?? '');
    }

    public function hookDisplayHeader(): string
    {
        return '<link rel="dns-prefetch" href="//cdntag.tarteaucitron.io">' . "\n"
            . '<link rel="preconnect" href="https://cdntag.tarteaucitron.io" crossorigin="">';
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
                'server'   => 'remote',
                'position' => self::JS_POSITION,
                'priority' => self::JS_PRIORITY_LOADER,
            ]
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
            ['position' => self::JS_POSITION, 'priority' => self::JS_PRIORITY_LOADER]
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
        if ($jscode === '') {
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
                ['position' => self::JS_POSITION, 'priority' => self::JS_PRIORITY_SERVICES]
            );
        }
    }
}
