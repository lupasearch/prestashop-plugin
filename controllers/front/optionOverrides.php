<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use LupaSearch\LupaSearchPlugin\ConfigurationConstants;
use LupaSearch\LupaSearchPlugin\ConfigurationResolver;
use LupaSearch\LupaSearchPlugin\Controllers\LupaModuleFrontController;

class LupaSearchOptionOverridesModuleFrontController extends LupaModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();

        $shop = $this->context->shop;

        $optionOverrides = trim(base64_decode((string) ConfigurationResolver::getMultilangValue(
            ConfigurationConstants::LUPA_UI_PLUGIN_OPTION_OVERRIDES,
            $this->context->language->id,
            $shop->id_shop_group,
            $shop->id
        )));

        if ($optionOverrides === '') {
            $this->sendJavaScript('window.__lupaSearchOptionOverrides = {};');
        }

        $this->sendJavaScript('window.__lupaSearchOptionOverrides = (' . $optionOverrides . ');');
    }
}
