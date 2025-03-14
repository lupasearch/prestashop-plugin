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
use LupaSearch\LupaSearchPlugin\DataProviders\VariantDataProvider;
use LupaSearch\LupaSearchPlugin\QueryProvider;
use Symfony\Component\HttpFoundation\Request;

class LupaSearchVariantModuleFrontController extends LupaModuleFrontController
{
    private $authorizationValidator;
    private $queryProvider;
    private $variantDataProvider;

    public function __construct()
    {
        parent::__construct();

        $this->authorizationValidator = new AuthorizationValidator();
        $this->queryProvider = new QueryProvider();
        $this->variantDataProvider = new VariantDataProvider($this->queryProvider);
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

        $this->sendJson($this->getVariants((int) $page, (int) $limit));
    }

    private function getVariants(int $page = 1, int $limit = 20): array
    {
        $shopId = Context::getContext()->shop->id;
        $totalItems = Db::getInstance()->getValue($this->queryProvider->getVariantsCount($shopId));

        return [
            'data' => $this->variantDataProvider->getFormattedVariants($page, $limit),
            'total' => (int) $totalItems,
            'limit' => $limit,
            'page' => $page,
            'totalPages' => ceil($totalItems / $limit),
        ];
    }
}
