<?php
/**
 * @author LupaSearch
 * @copyright LupaSearch
 * @license MIT
 */

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeValidator
{
    public static function validateAttributesExistence(array $target, array $attributeKeys): void
    {
        foreach ($attributeKeys as $key) {
            if (!array_key_exists($key, $target)) {
                throw new \Exception("Attribute key '$key' is not found within the given target structure: " . \json_encode($target));
            }
        }
    }
}
