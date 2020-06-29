<?php
namespace Provider;

use Psr\Http\Message\ServerRequestInterface as Request;
use stdClass;
use Provider\Provider;
use RuntimeException;
use Services\Utils;
use Google_Client;
use SebastianBergmann\ObjectEnumerator\InvalidArgumentException;
use GuzzleHttp\Client as GuzzleClient;

class ResponseHandler extends Response {
    private $request_input, $provider;
    function __construct(callable $initProvider, Request $request = null, stdClass $response_handling = null, stdClass $request_input = null, string $response_type = 'guzzle_http_client'){
        parent::__construct($response_type, $response_handling);
        $this->provider = $initProvider();
        if(!$this->provider instanceof Provider) throw new RuntimeException("The instance returned by the initProvider callable must be an instance of the Provider class");
        if(!$request_input){
            if(is_null($request)) throw new InvalidArgumentException("A request of the type Psr\Http\Message\ServerRequestInterface is required to determine the request input. Otherwise provide an argument for the request_input.");
            $this->request_input = Utils::getRequestInput($request);
        } else {
            $this->request_input = $request_input;
        }
    }
    /**
     * Return the response based on the specified handling parameters
     *
     * @param Logger $http_logger
     * @param GuzzleClient $guzzle_client
     * @param Google_Client $google_client
     * @param Logger $default_logger
     * @return void
     */
    function getResponse(Logger $http_logger, GuzzleClient $guzzle_client = null, Google_Client $google_client = null, Logger $default_logger){
        // get the request
        $request = $this->getRequest($http_logger, $this->provider, $this->request_input);

        // return the request
        if(isset($this->response_handling->return_request) && $this->response_handling->return_request == true)
            return $request;
        
        //get the response
        $response = $this->handleResponse($http_logger, $request, $guzzle_client, $google_client);

        // log the response
        if(isset($this->response_handling->log) && $this->response_handling->log == true){
            $this->logResponse($response, $http_logger);
        }

        //return an array representation of response
        if(isset($this->response_handling->decode_response) && $this->response_handling->decode_response == true){
            return $this->toArray($response);
        }

        // log additional information from the properties of the provider class
        if(isset($this->response_handling->log_additional_class_info) && count($this->response_handling->log_additional_class_info)){
            $this->logProviderPublicProps($this->provider, $default_logger);
        }

        // return response as derived from the parent::handleResponse method
        return $response;
    }
}