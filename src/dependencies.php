<?php
use Services\Log;
use Services\HTTP_Validation;
use Services\Error;
use Services\Utils;
use Slim\App;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Slim\Http\Request;
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
        return function (GuzzleResponse $response) use ($c) {
            $logger = $c->get('http_logger');
            Utils::logArrayContent(Utils::getGuzzleResponseInformation($response), $logger, 'info');
        };
    };

    // DEFAULT ERROR HANDLING STRATEGY
    $container['errorHandling'] = function($c) {
        return function(Request $request, Exception $exception, Response $response, string $error_level = 'critical', int $header_status = 500, string $error_desc = 'Default Error Handler : Caught Error') use ($c) {
            $error_obj = new Error($request, $exception, $error_desc);
            $error_logger = $c['error_logger'];
            Utils::logArrayContent($error_obj->error, $error_logger, $error_level);
            $error_message = $error_obj->error['Message'];
            $response = Utils::withAdditionalHeaders($response, [
                'Content-Type:application/json', 
                'Access-Control-Allow-Origin:*', 
                'Access-Control-Allow-Headers:X-Requested-With, Content-Type, Accept, Origin, Authorization',
                'Access-Control-Allow-Methods:GET, POST, PUT, DELETE, PATCH, OPTIONS'
            ]);
            $settings = $c->get('settings');
            $document_content = null;
            if(isset($settings['formatErrorResponse']) && is_callable($settings['formatErrorResponse'])){
                $document_content = $settings['formatErrorResponse']($header_status, $error_message);
            } else{
                $document_content = Utils::formatJsonResponse('', $error_message);
            }
            return $response->withStatus($header_status)->write($document_content);
        };
    };

    // DEFAULT ERROR HANDLING SERVICE
    $container['errorHandler'] = function($c) {
        return function (Request $request, Response $response, Exception $exception) use ($c) {
            $error_response = $c['errorHandling']($request, $exception, $response);
            return $error_response;
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

    // PusherJS
    $container['pusherjs_trigger'] = function($c){
        return function(string $event_name, int $status, string $message, string $pusher_channel_name) {
            foreach(['APP_KEY', 'APP_SECRET', 'APP_ID', 'APP_CLUSTER'] as $env_var){
                if(!isset($_ENV[$env_var])) throw new RuntimeException("The " . $env_var . " environment variable is not set. It is required to init the PusherJS server.");
            }
            $pusher = new Pusher\Pusher($_ENV['APP_KEY'], $_ENV['APP_SECRET'], $_ENV['APP_ID'], array('cluster' => $_ENV['APP_CLUSTER']));
            $pusher->trigger($pusher_channel_name, $_SERVER['REMOTE_ADDR'] . '|' . $event_name, array('message' => json_encode(['status' => $status, 'message' => $message])));
        };
    };

    // Service factory for the ORM
    $container['db'] = function ($c) use ($settings) {
        return function(array $connection_settings) use ($settings) {
            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection($connection_settings);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            return $capsule;
        };
    };
};