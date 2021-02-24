<?php

const NO_KEEP_STATISTIC = true;
const NO_AGENT_CHECK = true;

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use BX\Router\RestApplication;
use BX\Router\Router;
use BX\Router\Middlewares\Logger;
use BX\Router\Middlewares\Cache;
use BX\Router\Middlewares\HttpException;
use BX\Router\Middlewares\AuthJWT;
use Bx\Model\Services\UserService;
use Bx\JWT\Interfaces\UserTokenServiceInterface;
use Bx\JWT\Strategy\HS256TokenStrategy;
use Bx\JWT\UserDataPacker;
use Bx\JWT\UserTokenService;

Loader::includeModule('bx.model');
Loader::includeModule('bx.jwt');
Loader::includeModule('bx.router');

$app = new RestApplication();
$bitrixService = $app->getBitrixService();

$jwtHeader = (string)Option::get('bx.jwt', 'JWT_HTTP_HEADER', 'X-API-Key');
$ttl = (int)Option::get('bx.jwt', 'JWT_TTL', 86400);   // время жизни токена
$userService = new UserService();
$userTokenService = new UserTokenService(
    new HS256TokenStrategy(),
    new UserDataPacker($ttl, $userService),
    $userService
);

$logger = new Logger();
$defaultCache = new Cache(600);

$app->registerMiddleware(new Logger())
    ->addMiddleware(new HttpException($app->getFactory()))
    ->addMiddleware(new AuthJWT($jwtHeader, $userTokenService));

/**
 * @var Router $router
 */
$router = $app->getRouter();

$app->run();
