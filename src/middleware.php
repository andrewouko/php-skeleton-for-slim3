<?php
use Slim\App;
use Slim\Container;
use Services\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Services\Error;

function logServerState(Container $container)  {
    $logger = $container->get('system_logger');
    Utils::logArrayContent(Utils::getServerState(), $logger, 'debug');
}

function logRequestInformation(Container $container, Request $request) {
    $logger = $container->get('http_logger');
    $request_inforamtion = Utils::getRequestInformation($request);
    Utils::logArrayContent($request_inforamtion, $logger, 'debug');
}
function logResponseInformation(Container $container, Response $response) {
    $logger = $container->get('http_logger');
    Utils::logArrayContent(Utils::getResponseInformation($response), $logger, 'info');
}

return function (App $app, callable $validateRequest) {

    //entry middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $validateRequest) {
        $container = $app->getContainer();
        logServerState($container);
        logRequestInformation($container, $request);
        try{
            $validateRequest($container, $request);
        } catch(Exception $e){
            $error_obj = new Error($request, $e, 'Middleware Request Validation Error');
            // var_dump($error_obj->error);
            $error_logger = $container['error_logger'];
            Utils::logArrayContent($error_obj->error, $error_logger, 'error');
            return $response->withStatus(400)
            ->withHeader('Content-Type', 'application/json')
            ->write(Utils::formatJsonResponse('', $error_obj->error['Message']));
        }
        return $next($request, $response);
    });


    // exit middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app) {
        $container = $app->getContainer();
        $response = $next($request, $response);
        logResponseInformation($container, $response);
        return $response;
    });


};
