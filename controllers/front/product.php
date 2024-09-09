<?php

declare(strict_types=1);

use LupaSearch\LupaSearchPlugin\AuthorizationValidator;
use LupaSearch\LupaSearchPlugin\ProductDataProvider;
use LupaSearch\LupaSearchPlugin\QueryProvider;
use Symfony\Component\HttpFoundation\Request;

class LupaSearchProductModuleFrontController extends ModuleFrontController
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
            header('Content-Type: application/json');
            header('HTTP/1.1 403 Forbidden');
            $this->ajaxRender(json_encode($e->getMessage()));
            die();
        }

        $page = Tools::getValue('page', 1);
        $limit = Tools::getValue('limit', 20);

        header('Content-Type: application/json');
        $this->ajaxRender(json_encode($this->getProducts($page, $limit)));
        die();
    }

    private function getProducts(int $page = 1, int $limit = 20): array
    {
        $totalItems = Db::getInstance()->getValue($this->queryProvider->getProductsCount());

        return [
            'data' => $this->productDataProvider->getFormattedProducts($page, $limit),
            'total' => $totalItems,
            'limit' => $limit,
            'page' => $page,
            'totalPages' => ceil($totalItems / $limit),
        ];
    }
}
