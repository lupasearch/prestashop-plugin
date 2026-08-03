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
use LupaSearch\LupaSearchPlugin\ConfigurationResolver;

class LupaSearch extends Module
{
    private const LUPA_CONFIGURATION_SUBMIT_ACTION = 'submitLupaConfiguration';
    private const LUPA_HEADER_TEMPLATE_PATH = 'views/templates/front/header.tpl';

    protected const LUPA_CONFIGURATION_KEYS = [
        ConfigurationConstants::LUPA_WIDGET_ENABLED,
        ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY,
        ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES,
        ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
    ];

    protected const LUPA_MULTILANG_CONFIGURATION_KEYS = self::LUPA_CONFIGURATION_KEYS;

    public function __construct()
    {
        $this->name = 'lupasearch';
        $this->tab = 'search_filter';
        $this->version = '0.7.0';
        $this->author = 'LupaSearch';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '9.99.99'];

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

        foreach (Language::getLanguages(false) as $language) {
            $idLang = (int) $language['id_lang'];
            foreach (self::LUPA_MULTILANG_CONFIGURATION_KEYS as $key) {
                Configuration::deleteByName(ConfigurationResolver::buildLanguageKey($key, $idLang));
            }
        }

        return parent::uninstall();
    }

    public function getContent(): string
    {
        if (Tools::isSubmit(self::LUPA_CONFIGURATION_SUBMIT_ACTION)) {
            $shop = $this->context->shop;
            $this->postConfigurationSubmitAction($shop->id_shop_group, $shop->id);
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

        $shop = $this->context->shop;

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues($shop->id_shop_group, $shop->id),
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
                        'type' => 'html',
                        'name' => 'widget_enabled_language_override',
                        'html_content' => $this->buildLanguageOverrideField(
                            ConfigurationConstants::LUPA_WIDGET_ENABLED,
                            'select',
                            $this->l('Per-language override')
                        ),
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
                        'type' => 'html',
                        'name' => 'ui_plugin_configuration_key_language_override',
                        'html_content' => $this->buildLanguageOverrideField(
                            ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY,
                            'text',
                            $this->l('Per-language override')
                        ),
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('UI Plugin Option Overrides'),
                        'desc' => $this->l(
                            'Optional: Enter JSON-formatted configuration overrides for the LupaSearch UI plugin. Example: {"searchBox":{"dynamicData":{"enabled":true,"cache":true},"panels":[]},"searchResults":{"elements":[],"dynamicData":{"enabled":true,"cache":true}}}'
                        ),
                        'name' => ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES,
                        'cols' => 40,
                        'rows' => 5,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'ui_plugin_option_overrides_language_override',
                        'html_content' => $this->buildLanguageOverrideField(
                            ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES,
                            'textarea',
                            $this->l('Per-language override')
                        ),
                    ],
                    [
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l(
                            'Enter a valid LupaSearch product search index ID. Example: 3c6f1b6b-97ff-45b8-a168-d04e33c9996c'
                        ),
                        'name' => ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
                        'label' => $this->l('Product Index ID'),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'product_index_id_language_override',
                        'html_content' => $this->buildLanguageOverrideField(
                            ConfigurationConstants::LUPA_PRODUCT_INDEX_ID,
                            'text',
                            $this->l('Per-language override')
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Renders a <details> block (collapsed by default) containing one input per
     * shop language for the given setting's language override.
     */
    protected function buildLanguageOverrideField(string $key, string $type, string $summary): string
    {
        $shop = $this->context->shop;
        $currentValues = $this->getLanguageOverrideFormValues($key, $shop->id_shop_group, $shop->id);

        $rowsHtml = '';
        foreach (Language::getLanguages(false, $shop->id) as $language) {
            $idLang = (int) $language['id_lang'];
            $inputName = ConfigurationResolver::buildLanguageKey($key, $idLang);
            $value = (string) ($currentValues[$idLang] ?? '');

            $rowsHtml .= '<div style="margin-bottom:10px;">'
                . '<label style="font-weight:600;display:block;margin-bottom:4px;">' . htmlspecialchars($language['name']) . '</label>'
                . $this->buildLanguageOverrideInput($type, $inputName, $value)
                . '</div>';
        }

        return '<details>'
            . '<summary style="cursor:pointer;color:#337ab7;">' . htmlspecialchars($summary) . '</summary>'
            . '<div style="margin-top:10px;padding:10px;border:1px solid #ddd;border-radius:4px;">' . $rowsHtml . '</div>'
            . '</details>';
    }

    protected function buildLanguageOverrideInput(string $type, string $inputName, string $value): string
    {
        if ($type === 'select') {
            $options = [
                '' => $this->l('Inherit default'),
                '1' => $this->l('Enabled'),
                '0' => $this->l('Disabled'),
            ];
            $optionsHtml = '';
            foreach ($options as $optionValue => $optionLabel) {
                $selected = $value === $optionValue ? ' selected="selected"' : '';
                $optionsHtml .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>'
                    . htmlspecialchars($optionLabel) . '</option>';
            }

            return '<select name="' . htmlspecialchars($inputName) . '" class="form-control" style="max-width:300px;">'
                . $optionsHtml . '</select>';
        }

        if ($type === 'textarea') {
            return '<textarea name="' . htmlspecialchars($inputName) . '" class="form-control" rows="5" style="max-width:500px;">'
                . htmlspecialchars($value) . '</textarea>';
        }

        return '<input type="text" name="' . htmlspecialchars($inputName) . '" class="form-control" '
            . 'style="max-width:500px;" value="' . htmlspecialchars($value) . '" />';
    }

    protected function getConfigFormValues(int $shopGroupId, int $shopId): array
    {
        $values = [];

        foreach (self::LUPA_CONFIGURATION_KEYS as $key) {
            $value = Configuration::get($key, null, $shopGroupId, $shopId);
            $values[$key] = $key === ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES
                ? base64_decode((string) $value)
                : $value;
        }

        return $values;
    }

    protected function getLanguageOverrideFormValues(string $key, int $shopGroupId, int $shopId): array
    {
        $isBase64Encoded = $key === ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES;
        $values = [];

        foreach (Language::getLanguages(false, $shopId) as $language) {
            $idLang = (int) $language['id_lang'];
            $value = Configuration::get(
                ConfigurationResolver::buildLanguageKey($key, $idLang),
                null,
                $shopGroupId,
                $shopId
            );
            $values[$idLang] = $isBase64Encoded ? base64_decode((string) $value) : $value;
        }

        return $values;
    }

    protected function postConfigurationSubmitAction(int $shopGroupId, int $shopId): void
    {
        foreach (self::LUPA_CONFIGURATION_KEYS as $key) {
            $value = trim((string) Tools::getValue($key));
            Configuration::updateValue(
                $key,
                $key === ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES ? base64_encode($value) : $value,
                false,
                $shopGroupId,
                $shopId
            );
        }

        foreach (self::LUPA_MULTILANG_CONFIGURATION_KEYS as $key) {
            $this->postLanguageOverrideSubmitAction($key, $shopGroupId, $shopId);
        }
    }

    protected function postLanguageOverrideSubmitAction(string $key, int $shopGroupId, int $shopId): void
    {
        $isBase64Encoded = $key === ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES;

        foreach (Language::getLanguages(false, $shopId) as $language) {
            $idLang = (int) $language['id_lang'];
            $composedKey = ConfigurationResolver::buildLanguageKey($key, $idLang);
            $value = trim((string) Tools::getValue($composedKey));

            Configuration::updateValue(
                $composedKey,
                $isBase64Encoded ? base64_encode($value) : $value,
                false,
                $shopGroupId,
                $shopId
            );
        }
    }

    public function hookDisplayHeader(): string
    {
        $shop = $this->context->shop;
        $idLang = $this->context->language->id;

        $isWidgetEnabled = ConfigurationResolver::getMultilangValue(
            ConfigurationConstants::LUPA_WIDGET_ENABLED,
            $idLang,
            $shop->id_shop_group,
            $shop->id
        );
        $uiPluginConfigurationKey = ConfigurationResolver::getMultilangValue(
            ConfigurationConstants::LUPA_UI_PLUGIN_CONFIGURATION_KEY,
            $idLang,
            $shop->id_shop_group,
            $shop->id
        );
        $uiPluginOptionOverrides = base64_decode((string) ConfigurationResolver::getMultilangValue(
            ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES,
            $idLang,
            $shop->id_shop_group,
            $shop->id
        ));

        if ($isWidgetEnabled && $uiPluginConfigurationKey) {
            $smartyVariables = [
                'uiPluginConfigurationKey' => $uiPluginConfigurationKey,
            ];
            if (
                $uiPluginOptionOverrides
                && is_string($uiPluginOptionOverrides)
                && substr($uiPluginOptionOverrides, 0, 1) === '{'
                && substr($uiPluginOptionOverrides, -1) === '}'
            ) {
                $smartyVariables['uiPluginOptionOverrides'] = $uiPluginOptionOverrides;
            }

            $this->context->smarty->assign($smartyVariables);

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
            'module-lupasearch-variants' => [
                'controller' => 'variant',
                'rule' => 'rest/lupasearch/variants',
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
