<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Controllers;

use Exception;
use ModuleFrontController;

class LupaModuleFrontController extends ModuleFrontController
{
    const HTTP_CODE_OK = 200;
    const HTTP_CODE_FORBIDDEN = 403;

    const HTTP_TEXT = [
        self::HTTP_CODE_OK => 'OK',
        self::HTTP_CODE_FORBIDDEN => 'Forbidden',
    ];

    /**
     * @param string|array $content
     * @param int $statusCode
     * @return void
     * @throws Exception
     */
    public function sendJson($content, int $statusCode = 200): void
    {
        if (!isset(self::HTTP_TEXT[$statusCode])) {
            throw new Exception("HTTP_TEXT for status code (code: $statusCode) is not defined");
        }

        header('Content-Type: application/json');
        header("HTTP/1.1 $statusCode " . self::HTTP_TEXT[$statusCode]);
        $this->ajaxRender(json_encode($content));
        exit();
    }
}
