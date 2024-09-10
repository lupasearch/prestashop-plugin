<?php

declare(strict_types=1);

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
        $languageId = Context::getContext()->language->id;

        $result = [];

        foreach ($this->getAttributeGroups($languageId) as $group) {
            $result["attribute_group_{$group['id_attribute_group']}"] = $group['name'];
        }

        foreach ($this->getFeatures($languageId) as $feature) {
            $result["feature_{$feature['id_feature']}"] = $feature['name'];
        }

        return $result;
    }

    private function getAttributeGroups(int $languageId): array
    {
        return Db::getInstance()->executeS($this->queryProvider->getAttributeGroupsQuery($languageId));
    }

    private function getFeatures(int $languageId): array
    {
        return Db::getInstance()->executeS($this->queryProvider->getFeaturesQuery($languageId));
    }
}
