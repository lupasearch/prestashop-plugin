<?php

if (!defined('_PS_VERSION_')) {
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use LupaSearch\LupaSearchPlugin\ConfigurationConstants;

class LupaSearch extends Module
{
    private const LUPA_CONFIGURATION_SUBMIT_ACTION = 'submitLupaConfiguration';

    protected const LUPA_CONFIGURATION_KEYS = [
        ConfigurationConstants::LUPA_MODULE_ENABLED,
        ConfigurationConstants::LUPA_JS_PLUGIN_URL,
        ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
    ];

    public function __construct()
    {
        $this->name = 'lupasearch';
        $this->tab = 'search_filter';
        $this->version = '0.1.0';
        $this->author = 'LupaSearch';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '8.99.99'];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('LupaSearch integration');
        $this->description = $this->l('Integrates LupaSearch with your PrestaShop store.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
    }

    public function install(): bool
    {
        Configuration::updateValue(ConfigurationConstants::LUPA_MODULE_ENABLED, false);

        return parent::install() && $this->registerHook('displayHeader') && $this->registerHook('moduleRoutes');
    }

    public function uninstall(): bool
    {
        foreach (self::LUPA_CONFIGURATION_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function getContent(): string
    {
        if (Tools::isSubmit(self::LUPA_CONFIGURATION_SUBMIT_ACTION)) {
            $this->postConfigurationSubmitAction();
        }

        return $this->renderForm();
    }

    protected function renderForm(): string
    {
        $helper = new HelperForm();

        $helper->table = $this->table;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = self::LUPA_CONFIGURATION_SUBMIT_ACTION;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

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
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'module_notice',
                        'html_content' =>
                            $this->l(
                                'Welcome to the LupaSearch PrestaShop extension! To begin, please
              contact our support team by emailing '
                            ) .
                            '<a href="mailto:support@lupasearch.com">support@lupasearch.com</a>' .
                            $this->l('. Our team will assist you with your personalized search
              configuration and will provide you with a unique JavaScript URL.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable module'),
                        'name' => ConfigurationConstants::LUPA_MODULE_ENABLED,
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('Enter the URL of LupaSearch JavaScript plugin'),
                        'name' => ConfigurationConstants::LUPA_JS_PLUGIN_URL,
                        'label' => $this->l('JS plugin URL'),
                    ],
                    [
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Enter a valid LupaSearch product search index ID'),
                        'name' => ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
                        'label' => $this->l('Product Index ID'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues(): array
    {
        return [
            ConfigurationConstants::LUPA_MODULE_ENABLED => Configuration::get(
                ConfigurationConstants::LUPA_MODULE_ENABLED
            ),
            ConfigurationConstants::LUPA_JS_PLUGIN_URL => Configuration::get(
                ConfigurationConstants::LUPA_JS_PLUGIN_URL
            ),
            ConfigurationConstants::LUPA_PRODUCT_INDEX_ID => Configuration::get(
                ConfigurationConstants::LUPA_PRODUCT_INDEX_ID
            ),
        ];
    }

    protected function postConfigurationSubmitAction(): void
    {
        foreach (self::LUPA_CONFIGURATION_KEYS as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookDisplayHeader(): void
    {
        $jsPluginUrl = Configuration::get(ConfigurationConstants::LUPA_JS_PLUGIN_URL);
        $isModuleEnabled = Configuration::get(ConfigurationConstants::LUPA_MODULE_ENABLED);

        if ($isModuleEnabled && $jsPluginUrl) {
            $this->context->controller->registerJavascript('lupasearch-head-plugin-js', $jsPluginUrl, [
                'position' => 'head',
                'priority' => 150,
                'server' => 'remote',
            ]);
        }
    }

    public function hookModuleRoutes(): array
    {
        return [
            'module-lupasearch-products' => [
                'controller' => 'product',
                'rule' => 'rest/lupasearch/products',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'lupasearch',
                ],
            ],
            'module-lupasearch-properties' => [
                'controller' => 'property',
                'rule' => 'rest/lupasearch/properties',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'lupasearch',
                ],
            ],
        ];
    }
}