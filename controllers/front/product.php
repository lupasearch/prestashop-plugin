<?php

declare(strict_types=1);

use LupaSearch\LupaSearchPlugin\AuthorizationValidator;
use LupaSearch\LupaSearchPlugin\Controllers\LupaModuleFrontController;
use LupaSearch\LupaSearchPlugin\ProductDataProvider;
use LupaSearch\LupaSearchPlugin\QueryProvider;
use Symfony\Component\HttpFoundation\Request;

class LupaSearchProductModuleFrontController extends LupaModuleFrontController
{
    private $authorizationValidator;
    private $productDataProvider;
    private $queryProvider;

    public function __construct()
    {
        parent::__construct();

        $this->authorizationValidator = new AuthorizationValidator();
        $this->queryProvider = new QueryProvider();
        $this->productDataProvider = new ProductDataProvider($this->queryProvider);
    }

    public function initContent(): void
    {
        parent::initContent();

        try {
            $this->authorizationValidator->validateRequest(Request::createFromGlobals());
        } catch (Throwable $e) {
            $this->sendJson($e->getMessage(), 403);
        }

        $page = Tools::getValue('page', 1);
        $limit = Tools::getValue('limit', 20);

        if (!Validate::isUnsignedInt($page) || $page < 1) {
            $this->sendJson('Invalid page parameter', 400);
        }

        if (!Validate::isUnsignedInt($limit) || $limit < 1) {
            $this->sendJson('Invalid limit parameter', 400);
        }

        $this->sendJson($this->getProducts(intval($page), intval($limit)));
    }

    private function getProducts(int $page = 1, int $limit = 20): array
    {
        $totalItems = Db::getInstance()->getValue($this->queryProvider->getProductsCount());

        return [
            'data' => $this->productDataProvider->getFormattedProducts($page, $limit),
            'total' => intval($totalItems),
            'limit' => $limit,
            'page' => $page,
            'totalPages' => ceil($totalItems / $limit),
        ];
    }
}
