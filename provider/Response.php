<?php
namespace Provider;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use InvalidArgumentException;
use stdClass;
use Provider\Provider;
use Services\Utils;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Google_Client;
use Monolog\Logger;
use GuzzleHttp\Client;

abstract class Response {
    protected $response_handling, $response_type;
    static $GuzzleResponse = 'guzzzle_http_client';
    static $GoogleResponse = 'google_client';
    function __construct(string $response_type, stdClass $response_handling = null){
        if($response_type != self::$GoogleResponse || $response_type != self::$GuzzleResponse) throw new InvalidArgumentException("The response type must be one of " . json_encode([self::$GoogleResponse, self::$GuzzleResponse]));
        $this->response_type = $response_type;
        foreach(['log', 'decode_response', 'return_request', 'log_additional_class_info'] as $param){
            if(isset($response_handling->$param)){
                switch($param){
                    case 'log':
                    case 'decode_response':
                    case 'return_request':
                        if(!is_bool($response_handling->$param)) throw new InvalidArgumentException($param . " must be a boolean value. Provided: " . gettype($response_handling->$param) . ' Value: ' . $response_handling->$param);
                        break;
                    case 'log_additional_class_info':
                        if(!is_array($response_handling->$param)) throw new InvalidArgumentException($param . " must be an array. Provided: " . gettype($response_handling->$param));
                        break;
                    default:
                    break;
                }
            }
        }
        $this->response_handling = $response_handling;
    }
    /**
     * Get a guzzle request from a given provider and log the curl command equivalent for the request
     *
     * @param Logger $http_logger
     * @param Provider $provider
     * @param stdClass $request_input
     * @return GuzzleRequest
     */
    protected function getRequest(Logger $http_logger, Provider $provider, stdClass $request_input = null):GuzzleRequest{
        // get the guzzle request for all operations in a unified manner
        $request = $provider->getRequest($request_input);

        //log the request as a curl command for debugging
        $curl_command = (new CurlFormatter())->format($request, []);
        Utils::logArrayContent(['curl_command' => $curl_command], $http_logger, 'debug');

        return $request;
    }
    /**
     * Process a request using the Guzzle client in the dependencies and return a response 
     *
     * @param Client $client
     * @param GuzzleRequest $request
     * @return GuzzleResponse
     */
    private function getGuzzleResponse(Client $client, GuzzleRequest $request):GuzzleResponse{
        $response = $client->send($request);
        return $response;
    }
    /**
     * Process a request using the google client and return a response
     *
     * @param Google_Client $client
     * @param GuzzleRequest $request
     * @return ResponseInterface
     */
    private function getGoogleResponse(Google_Client $client, GuzzleRequest $request):ResponseInterface{
        $response =  $client->execute($request);
        return $response;
    }
    /**
     * Get the response based on the response type
     *
     * @param Logger $http_logger
     * @param Client $guzzle_client
     * @param GuzzleRequest $request
     * @param Google_Client $client
     * @return ResponseInterface
     */
    protected function handleResponse(Logger $http_logger, GuzzleRequest $request, Client $guzzle_client = null, Google_Client $google_client = null):ResponseInterface{
        Utils::logArrayContent(Utils::getGuzzleRequestInformation($request), $http_logger, 'info');
        if($this->response_type == self::$GuzzleResponse)
            $response = $this->getGuzzleResponse($guzzle_client, $request);
        if($this->response_type == self::$GoogleResponse)
            $response = $this->getGoogleResponse($google_client, $request);
        return $response;
    }
    /**
     * Log the response from the Guzzle client
     *
     * @param Logger $logger
     * @param GuzzleResponse $response
     * @return void
     */
    private function logGuzzleResponse(Logger $http_logger, GuzzleResponse $response){
        Utils::logArrayContent(Utils::getGuzzleResponseInformation($response), $http_logger, 'info');
    }
    /**
     * Log the response based on Response::response_type
     *
     * @param ResponseInterface $response
     * @param Logger $http_logger
     * @return void
     */
    protected function logResponse(ResponseInterface $response, Logger $http_logger){
        if($this->response_type == self::$GuzzleResponse)
            $this->logGuzzleResponse($http_logger, $response);
        if($this->response_type == self::$GoogleResponse)
            Utils::logArrayContent(Utils::getResponseInformation($response), $http_logger, 'info');
    }
    /**
     * Convert a response to array
     *
     * @param ResponseInterface $response
     * @return array
     */
    protected function toArray(ResponseInterface $response):array{
        $string_response = (string) $response->getBody();

        //handler for json responses
        $json_decoded_response = json_decode($string_response, true);
        if($json_decoded_response) return $json_decoded_response;

        //handler for xml responses if necessary
        $xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $string_response);
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($xml_response);
        if($sxe){
            $array = json_decode(json_encode($sxe), TRUE);
            if($array)
                return $array;
        }
        throw new RuntimeException("Unable to convert the response to an array. String representation of response: " . $string_response);
    }
    /**
     * Log the public properties of the Provider class
     *
     * @param Provider $provider
     * @return void
     */
    protected function logProviderPublicProps(Provider $provider, Logger $default_logger){
        $log_content = [];
        foreach($this->response_handling->log_additional_class_info as $class_prop){
            if(isset($provider->$class_prop)){
                $log_content[$class_prop] = $provider->$class_prop;
            }
        }
        Utils::logArrayContent($log_content, $default_logger, 'info');
    }
}