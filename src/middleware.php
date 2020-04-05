<?php
use Slim\App;
use Slim\Container;
use Services\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Services\Error;

function logServerState(Container $container)  {
    $logger = $container->get('system_logger');
    Utils::logArrayContent(array_merge(Utils::getServerState()), $logger, 'debug');
}
function logResponseInformation(Container $container, Response $response) {
    $logger = $container->get('http_logger');
    Utils::logArrayContent(Utils::getResponseInformation($response), $logger, 'info');
}
function logRequestInformation(Container $container, Request $request) {
    $logger = $container->get('http_logger');
    $request_inforamtion = Utils::getRequestInformation($request);
    Utils::logArrayContent($request_inforamtion, $logger, 'debug');
}
$middlewareHandler = function(string $name, array $middleware_callables, App $app, Request $request, Response $response){
    try{
        foreach($middleware_callables as $callable){
            if(!is_callable($callable)) throw new InvalidArgumentException("Each element of the middleware_callables array must be a callable");
            $callable($app, $request);
        }
    } catch(Exception $e){
        $error_obj = new Error($request, $e, ucwords(strtolower($name)) . ' Error');
        // var_dump($error_obj->error);
        $container = $app->getContainer();
        $error_logger = $container['error_logger'];
        Utils::logArrayContent($error_obj->error, $error_logger, 'error');
        return $response->withStatus(400)
        ->withHeader('Content-Type', 'application/json')
        ->write(Utils::formatJsonResponse('', $error_obj->error['Message']));
    }
    return;
};

// cors middleware

return function (App $app, array $entry_middleware_callables = [], array $exit_middleware_callables = []) use ($middlewareHandler) {

    // entry middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $entry_middleware_callables, $middlewareHandler) {
        $container = $app->getContainer();
        logServerState($container);
        logRequestInformation($container, $request);
        $res = $middlewareHandler('Entry Middleware', $entry_middleware_callables, $app, $request, $response);
        if($res){
            $response = $res;
        }
        return $next($request, $response);
    });

    // exit middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $middlewareHandler, $exit_middleware_callables) {
        $container = $app->getContainer();
        $response = $next($request, $response);
        logResponseInformation($container, $response);
        $res = $middlewareHandler('Exit Middleware', $exit_middleware_callables, $app, $request, $response);
        if($res){
            $response = $res;
        }
        return $next($request, $response);
    });


    //cors middleware
    $app->add(function (Request $request, Response $response, callable $next) {
        $response = $next($request, $response);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->write("Benchod!\n");
    });
};
