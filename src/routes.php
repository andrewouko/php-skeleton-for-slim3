<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Provider\Interswitch\APIHealth;
use Provider\Interswitch\BillerInformation;
use Provider\Interswitch\Interswitch;
use Provider\Interswitch\SendAdviceRequest;
use Provider\Interswitch\TransactionsInquirys;
use Provider\Interswitch\QueryTransaction;

return function (App $app) {
    $container = $app->getContainer();

    $processRequest = function(Interswitch $isw_request, stdClass $input = null) use ($container) {
        return $container['external_request_handler']($isw_request->getRequest($input));
    };

    $BillersPaymentItemsRoute = function(string $country) use ($app, $processRequest) {
        $app->get('/billers/{biller_id}/items', function (Request $request, Response $response, array $args) use ($processRequest, $country) {
            $input = (object)['billerId' => $args['biller_id']];
            return $processRequest(new BillerInformation('payment_items', $country), $input);
        });
    };

    $BillersRoute = function(string $country) use ($app, $processRequest) {
        $app->get('/billers', function (Request $request, Response $response, array $args) use ($processRequest, $country) {
            return $processRequest(new BillerInformation('billers', $country));
        });
    };

    // Payment
    $PaymentRoute = function(string $country) use ($app, $processRequest) {
        $app->post('/payment', function (Request $request, Response $response, array $args) use ($processRequest, $country) {
            $isw_request = new SendAdviceRequest($country);
            $body = $request->getParsedBody();
            $body['requestReference'] = $isw_request->getRequestReference();
            return $processRequest($isw_request, (object) $body);
        });
    };
    
    $app->group('/ke', function(App $app) use ($processRequest, $BillersPaymentItemsRoute, $BillersRoute, $PaymentRoute) {
        // Interswith API Health
        $app->get('/health', function (Request $request, Response $response, array $args) use ($processRequest) {
            return $processRequest(new APIHealth());
        });

        // Kenya Biller Information
        $BillersRoute('kenya');
        
        // Kenya Billers Payment Items
        $BillersPaymentItemsRoute('kenya');

        $PaymentRoute('kenya');
        

        // Query Transaction Status using the requestReference
        $app->get('/transaction/status', function (Request $request, Response $response, array $args) use ($processRequest) {
            $params = $request->getQueryParams();
            return $processRequest(new QueryTransaction(), (object)['request_ref' => $params['requestReference']]);
        });
    });

    $app->group('/ug', function(App $app) use ($processRequest, $BillersRoute, $BillersPaymentItemsRoute, $PaymentRoute) {

        // Biller Category Information
        $app->get('/categorys', function (Request $request, Response $response, array $args) use ($processRequest) {
            return $processRequest(new BillerInformation('categorys'));
        });

        // Uganda Biller Information
        $BillersRoute('uganda');
        
        // Uganda Billers Payment Items
        $BillersPaymentItemsRoute('uganda');


        // Validate Customer
        $app->post('/validate', function (Request $request, Response $response, array $args) use ($processRequest) {
            return $processRequest(new TransactionsInquirys(), (object) $request->getParsedBody());
        });

        $PaymentRoute('uganda');
    });
};
