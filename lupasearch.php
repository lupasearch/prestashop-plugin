<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use LupaSearch\LupaSearchPlugin\ConfigurationConstants;

class LupaSearch extends Module
{
    private const LUPA_CONFIGURATION_SUBMIT_ACTION = 'submitLupaConfiguration';
    private const LUPA_HEADER_TEMPLATE_PATH = 'views/templates/front/header.tpl';

    protected const LUPA_CONFIGURATION_KEYS = [
        ConfigurationConstants::LUPA_WIDGET_ENABLED,
        ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY,
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
        Configuration::updateValue(ConfigurationConstants::LUPA_WIDGET_ENABLED, false);

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
            Tools::clearSmartyCache();
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
                        'html_content' => $this->l(
                            'Welcome to the LupaSearch PrestaShop extension! To begin, please
              contact our support team by emailing '
                        ) .
                            '<a href="mailto:support@lupasearch.com">support@lupasearch.com</a>' .
                            $this->l('. Our team will assist you with your personalized search
              configuration and will provide you with a unique UI Plugin Configuration Key.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable widget'),
                        'name' => ConfigurationConstants::LUPA_WIDGET_ENABLED,
                        'is_bool' => true,
                        'desc' => $this->l('Use this widget in live mode'),
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
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l(
                            'Enter the configuration key of LupaSearch UI plugin. Example: "p-xxxxxxxxxxxx"'
                        ),
                        'name' => ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY,
                        'label' => $this->l('UI Plugin Configuration Key'),
                    ],
                    [
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l(
                            'Enter a valid LupaSearch product search index ID. Example: 3c6f1b6b-97ff-45b8-a168-d04e33c9996c'
                        ),
                        'name' => ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
                        'label' => $this->l('Product Index ID'), ],
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
            ConfigurationConstants::LUPA_WIDGET_ENABLED => Configuration::get(
                ConfigurationConstants::LUPA_WIDGET_ENABLED
            ),
            ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY => Configuration::get(
                ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY
            ),
            ConfigurationConstants::LUPA_PRODUCT_INDEX_ID => Configuration::get(
                ConfigurationConstants::LUPA_PRODUCT_INDEX_ID
            ),
        ];
    }

    protected function postConfigurationSubmitAction(): void
    {
        foreach (self::LUPA_CONFIGURATION_KEYS as $key) {
            Configuration::updateValue($key, trim(Tools::getValue($key)));
        }
    }

    public function hookDisplayHeader(): string
    {
        $uiPluginConfigurationKey = Configuration::get(ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY);
        $isWidgetEnabled = Configuration::get(ConfigurationConstants::LUPA_WIDGET_ENABLED);

        if ($isWidgetEnabled && $uiPluginConfigurationKey) {
            $this->context->smarty->assign([
                'uiPluginConfigurationKey' => $uiPluginConfigurationKey,
            ]);

            return $this->display(__FILE__, self::LUPA_HEADER_TEMPLATE_PATH);
        }

        return '';
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
