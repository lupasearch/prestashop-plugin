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

use LupaSearch\LupaSearchPlugin\AuthorizationValidator;
use LupaSearch\LupaSearchPlugin\Controllers\LupaModuleFrontController;
use LupaSearch\LupaSearchPlugin\QueryProvider;
use Symfony\Component\HttpFoundation\Request;

class LupaSearchPropertyModuleFrontController extends LupaModuleFrontController
{
    private $authorizationValidator;
    private $queryProvider;

    public function __construct()
    {
        parent::__construct();

        $this->authorizationValidator = new AuthorizationValidator();
        $this->queryProvider = new QueryProvider();
    }

    public function initContent(): void
    {
        parent::initContent();

        try {
            $this->authorizationValidator->validateRequest(Request::createFromGlobals());
        } catch (Throwable $e) {
            $this->sendJson($e->getMessage(), 403);
        }

        $this->sendJson($this->getProperties());
    }

    private function getProperties(): array
    {
        $context = Context::getContext();
        $shopId = $context->shop->id;
        $languageId = $context->language->id;

        $result = [];

        foreach ($this->getAttributeGroups($shopId, $languageId) as $group) {
            $result["attribute_group_{$group['id_attribute_group']}"] = $group['name'];
        }

        foreach ($this->getFeatures($shopId, $languageId) as $feature) {
            $result["feature_{$feature['id_feature']}"] = $feature['name'];
        }

        return $result;
    }

    private function getAttributeGroups(int $shopId, int $languageId): array
    {
        return Db::getInstance()->executeS($this->queryProvider->getAttributeGroupsQuery($shopId, $languageId));
    }

    private function getFeatures(int $shopId, int $languageId): array
    {
        return Db::getInstance()->executeS($this->queryProvider->getFeaturesQuery($shopId, $languageId));
    }
}
