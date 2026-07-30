<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ConfigurationResolver
{
    private const LANGUAGE_KEY_SEPARATOR = '_LANG_';

    /**
     * Per-language overrides are stored under their own independent
     * Configuration key (e.g. "KEY_LANG_1"), rather than relying on
     * Configuration's own id_lang column, so a language override can never
     * collide with the shop-wide default value stored under "KEY".
     */
    public static function buildLanguageKey(string $key, int $idLang): string
    {
        return $key . self::LANGUAGE_KEY_SEPARATOR . $idLang;
    }

    /**
     * Reads the per-language override, falling back to the shop-wide default
     * when no language-specific value has been set.
     *
     * @return mixed
     */
    public static function getMultilangValue(
        string $key,
        int $idLang,
        ?int $idShopGroup = null,
        ?int $idShop = null
    ) {
        $languageValue = \Configuration::get(self::buildLanguageKey($key, $idLang), null, $idShopGroup, $idShop);

        if ($languageValue !== false && $languageValue !== null && $languageValue !== '') {
            return $languageValue;
        }

        return \Configuration::get($key, null, $idShopGroup, $idShop);
    }
}
