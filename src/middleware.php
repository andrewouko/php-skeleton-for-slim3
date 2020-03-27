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

return function (App $app, array $entry_middleware_callables = []) {

    //entry middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $entry_middleware_callables) {
        $container = $app->getContainer();
        logServerState($container);
        logRequestInformation($container, $request);
        try{
            foreach($entry_middleware_callables as $callable){
                if(!is_callable($callable)) throw new InvalidArgumentException("Each element of the entry_middleware_callables array must be a callable");
                $callable($container, $request);
            }
        } catch(Exception $e){
            $error_obj = new Error($request, $e, 'Entry Middleware Error');
            // var_dump($error_obj->error);
            $error_logger = $container['error_logger'];
            Utils::logArrayContent($error_obj->error, $error_logger, 'error');
            return $response->withStatus(400)
            ->withHeader('Content-Type', 'application/json')
            ->write(Utils::formatJsonResponse('', $error_obj->error['Message']));
        }
        return $next($request, $response);
    });


    // mandatory exit middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app) {
        $container = $app->getContainer();
        $response = $next($request, $response);
        logResponseInformation($container, $response);
        return $response;
    });


};
