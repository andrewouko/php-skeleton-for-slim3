<?php
define('VALID_API_PATHS', json_encode(['transaction/inquirys', 'ke/health', '', '', 'ug/categorys', 'ug/validate', 'ke/transaction/status']));
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

function validateRequest(Container $container, Request $request){
    $validation =  $container->get('validation');
    $valid_paths = json_decode(VALID_API_PATHS, true);
    $uri = $request->getUri();
    $parameters = [];
    $body = null;
    switch($uri->getPath()){
        // Transaction Inquiry
        case $valid_paths[0]:
            $parameters = [
                'paymentCode' => $validation->setParameterMetadata('numeric', 20),
                'customerId' => $validation->setParameterMetadata('alphanumeric', 50)
            ];
            break;
        case $valid_paths[5]:
            $parameters = [
                'paymentCode' => $validation->setParameterMetadata('numeric'),
                'customerId' => $validation->setParameterMetadata('alphanumeric'),
                'customerMobile' => $validation->setParameterMetadata('optional'),
                // 'requestReference'=> $validation->setParameterMetadata('alphanumeric', 12),
                'terminalId' => $validation->setParameterMetadata('alphanumeric', 8),
                'itemCode' => $validation->setParameterMetadata('optional'),
                'bankCbnCode' => $validation->setParameterMetadata('alphanumeric', 8),
                'amount' => $validation->setParameterMetadata('integer'),
                'deviceTerminalId' => $validation->setParameterMetadata('optional', 8),
                'customerEmail' => $validation->setParameterMetadata('optional'),
            ];
        break;
        case $valid_paths[6]:
            $parameters = ['requestReference' => $validation->setParameterMetadata('alphanumeric', 20)];
            $body = (object) $request->getQueryParams();
        break;
        // Get Requests, no validation required
        case $valid_paths[1]:
        case $valid_paths[4];
            return true;
        default:
            $path = strtolower($uri->getPath());
            // Biller Payment Items GET Request
            if(preg_match('/^(ke|ug)\/billers\/\d{1,}\/items/', $path)) return true;
            // Billers Request
            if(preg_match('/^(ke|ug)\/billers/', $path)) return true;
            // Payment Request
            if(preg_match('/^(ke|ug)\/payment/', $path)){
                if(substr($path, 0, 2) == 'ke'){
                    $parameters = [
                        'amount' => $validation->setParameterMetadata('integer'),
                        'paymentCode' => $validation->setParameterMetadata('numeric'),
                        'customerId' => $validation->setParameterMetadata('alphanumeric'),
                        'customerMobile' => $validation->setParameterMetadata('optional'),
                        // 'requestReference'=> $validation->setParameterMetadata('alphanumeric', 12),
                        // 'terminalId' => $validation->setParameterMetadata('alphanumeric', 8),
                        // 'bankCbnCode' => $validation->setParameterMetadata('alphanumeric', 8),
                        'surcharge' => $validation->setParameterMetadata('optional'),
                        'customerEmail' => $validation->setParameterMetadata('optional'),
                        'itemCode' => $validation->setParameterMetadata('optional'),
                        // 'transactionRef' => $validation->setParameterMetadata('alphanumeric'),
                        'narration' => $validation->setParameterMetadata('optional'),
                        'depositorName' => $validation->setParameterMetadata('optional'),
                        'alternateCustomerId' => $validation->setParameterMetadata('optional'),
                        'productReference' => $validation->setParameterMetadata('optional'),
                        'location' => $validation->setParameterMetadata('optional')
                    ];
                    break;
                }
                if(substr($path, 0, 2) == 'ug'){
                    $parameters = [
                        'paymentCode' => $validation->setParameterMetadata('numeric'),
                        'customerId' => $validation->setParameterMetadata('alphanumeric'),
                        'customerMobile' => $validation->setParameterMetadata('optional'),
                        // 'requestReference'=> $validation->setParameterMetadata('alphanumeric', 12),
                        'terminalId' => $validation->setParameterMetadata('alphanumeric', 8),
                        'itemCode' => $validation->setParameterMetadata('optional'),
                        'bankCbnCode' => $validation->setParameterMetadata('alphanumeric', 8),
                        'amount' => $validation->setParameterMetadata('integer'),
                        'deviceTerminalId' => $validation->setParameterMetadata('optional', 8),
                        'customerEmail' => $validation->setParameterMetadata('optional'),
                    ];
                    break;
                }
            }
            throw new Exception("Invalid request path provided: " . $path . ". Valid paths: " . VALID_API_PATHS);
    }
    $validation->setParameters($parameters);
    if(!$body){
        $body = (object) $request->getParsedBody();
    }
    return $validation->validateInput($body);
}

function logResponseInformation(Container $container, Response $response) {
    $logger = $container->get('http_logger');
    Utils::logArrayContent(Utils::getResponseInformation($response), $logger, 'info');
}

return function (App $app) {

    //entry middleware
    $app->add(function (Request $request, Response $response, callable $next) use ($app) {
        $container = $app->getContainer();
        logServerState($container);
        logRequestInformation($container, $request);
        try{
            validateRequest($container, $request);
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
