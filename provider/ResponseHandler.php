<?php
namespace Provider;

use Slim\Http\Request;
use InvalidArgumentException;
use stdClass;
use Provider\Provider;
use RuntimeException;
use Slim\Container;
use Services\Utils;
use SimpleXMLElement;
use Google_Client;
use Psr\Http\Message\ResponseInterface;

class ResponseHandler extends Response {
    private $request_input, $provider, $slim_request;
    function __construct(callable $initProvider, Request $request, stdClass $response_handling = null, stdClass $request_input = null, string $response_type = 'guzzle_http_client'){
        parent::__construct($response_type, $response_handling);
        $this->provider = $initProvider();
        if(!$this->provider instanceof Provider) throw new RuntimeException("The instance returned by the initProvider callable must be an instance of the Provider class");
        $this->slim_request = $request;
        if(!$request_input){
            $this->request_input = Utils::getRequestInput($this->slim_request);
        } else {
            $this->request_input = $request_input;
        }
    }
    /**
     * Return the response based on the specified handling parameters
     *
     * @param Container $container
     * @param Google_Client $client
     * @return GuzzleHttp\Psr7\Request|array|Psr\Http\Message\ResponseInterface
     */
    function getResponse(Container $container, Google_Client $client = null){
        // get the request
        $request = $this->getRequest($container, $this->provider, $this->request_input);

        // return the request
        if(isset($this->response_handling->return_request) && $this->response_handling->return_request == true)
            return $request;
        
        //get the response
        $response = $this->handleResponse($container, $request, $client);

        // log the response
        if(isset($this->response_handling->log) && $this->response_handling->log == true){
            $this->logResponse($container, $response);
        }

        //return an array representation of response
        if(isset($this->response_handling->decode_response) && $this->response_handling->decode_response == true){
            return $this->toArray($response);
        }

        // log additional information from the properties of the provider class
        if(isset($this->response_handling->log_additional_class_info) && count($this->response_handling->log_additional_class_info)){
            $this->logProviderPublicProps($this->provider);
        }

        // return response as derived from the parent::getResponse method
        return $response;
    }
}