<?php
require 'vendor/autoload.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
$app = AppFactory::create();
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/cors.php';
require __DIR__ . '/routes/users.php';
require __DIR__ . '/routes/offers.php';
// Esto maneja TODAS las solicitudes OPTIONS y evita el error 405
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->run();
