<?php
use Services\Log;
use Services\HTTP_Validation;
use Services\Error;
use Services\Utils;
use Slim\App;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();
    $settings = $container->get('settings');

    // view renderer
    // $container['renderer'] = function ($c) use ($settings) {
    //     return new \Slim\Views\PhpRenderer($settings['renderer']['template_path']);
    // };

    // -- LOGGING SERVICE INSTANCES
    $container['default_logger'] = function ($c) use ($settings) {
        $default_logger = new Log($settings['application']['name'], 'Default', $settings['log']['handler']);
        $default_logger = $default_logger->getLogger();
        return $default_logger;
    };

    $container['system_logger'] = function ($c) use ($settings) {
        $system_logger = new Log($settings['application']['name'], 'System Log', $settings['log']['handler'], false, true, false, true);
        $system_logger = $system_logger->getLogger();
        return $system_logger;
    };

    $container['http_logger'] = function ($c) use ($settings) {
        $http_logger = new Log($settings['application']['name'], 'HTTP Log', $settings['log']['handler'], true, false, true, false);
        $http_logger = $http_logger->getLogger();
        return $http_logger;
    };

    $container['error_logger'] = function ($c) use ($settings) {
        $error_logger = new Log($settings['application']['name'], 'Error Log', $settings['log']['handler'],true,true,false,true);
        $error_logger = $error_logger->getLogger();
        return $error_logger;
    };

    // HTTP REQUEST VALIDATION SERVICE
    $container['http_validation'] = function($c) {
        return new HTTP_Validation();
    };

    // LOG Ps-r 7 response
    $container['response-logger'] = function($c) {
        return function ($response) use ($c) {
            if(!$response instanceof Response || !$response instanceof GuzzleResponse) throw new InvalidArgumentException("Invalid Response type provided. Supported types are Response or GuzzleResponse. Provided: " . gettype($response));
            $logger = $c->get('http_logger');
            Utils::logArrayContent(Utils::getResponseInformation($response), $logger, 'info');
        };
    };

    // DEFAULT ERROR HANDLING SERVICE
    $container['errorHandler'] = function($c) {
        return function (Slim\Http\Request $request, Slim\Http\Response $response, Exception $exception) use ($c) {
            // var_dump($response);
            $error_obj = new Error($request, $exception, 'Default Error Handler : Caught Error');
            // var_dump($error_obj->error);
            $error_logger = $c['error_logger'];
            Utils::logArrayContent($error_obj->error, $error_logger, 'critical');
            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->write(Utils::formatJsonResponse('', $error_obj->error['Message']));
                // ->write("Something went wrong!\n" . $error_obj->error['resolution']);
        };
    };

    // HTTP CLIENT
    $container['http_client'] = function($c) {
        return new Client();
    };


    // Handler for requests to external providers
    $container['external_request_handler'] = function($c) {
        return function(GuzzleRequest $request) use ($c) : GuzzleResponse {
            $client = $c['http_client'];
            $logger = $c['http_logger'];
            Utils::logArrayContent(Utils::getGuzzleRequestInformation($request), $logger, 'info');
            $response = $client->send($request);
            return $response;
        };
    };

};