<?php
use Slim\App;
use Services\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

function logServerState(Logger $logger)  {
    Utils::logArrayContent(array_merge(Utils::getServerState()), $logger, 'debug');
}
function logRequestInformation(Logger $logger, Request $request) {
    $request_inforamtion = Utils::getRequestInformation($request);
    Utils::logArrayContent($request_inforamtion, $logger, 'debug');
}

function logIPAddressInformation(Request $request, Logger $logger){
    $ipAddress = $request->getAttribute('ip_address');
    Utils::logArrayContent(['ip_address' => $ipAddress], $logger, 'info');
}

$middlewareHandler = function(string $name, array $middleware_callables, App $app, Request $request, Response $response){
    try{
        foreach($middleware_callables as $callable){
            if(!is_callable($callable)) throw new InvalidArgumentException("Each element of the middleware_callables array must be a callable");
            $callable($app, $request);
        }
    } catch(Exception $e){
        $container = $app->getContainer();
        $error_response = $container['errorHandling']($request, $e, $response, 'error', 400, ucwords(strtolower($name)) . ' Error');
        return $error_response;
    }
    return;
};

return function (App $app, array $entry_middleware_callables = [], array $exit_middleware_callables = []) use ($middlewareHandler) {
    // ip address middleware
    $checkProxyHeaders = true;
    $trustedProxies = ['10.0.0.1', '10.0.0.2'];
    $app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));

    // entry middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $entry_middleware_callables, $middlewareHandler) {
        $container = $app->getContainer();
        logIPAddressInformation($request, $container->get('http_logger'));
        logServerState($container->get('system_logger'));
        logRequestInformation($container->get('http_logger'), $request);
        $middleware_response = $middlewareHandler('Entry Middleware', $entry_middleware_callables, $app, $request, $response);
        if($middleware_response){
            return $middleware_response;
        }
        return $next($request, $response);
    });

    // exit middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app, $middlewareHandler, $exit_middleware_callables) {
        $container = $app->getContainer();
        $response = $next($request, $response);
        $container['response-logger']($response);
        $middleware_response = $middlewareHandler('Exit Middleware', $exit_middleware_callables, $app, $request, $response);
        if($middleware_response){
            return $middleware_response;
        }
        return $response;
    });
};
