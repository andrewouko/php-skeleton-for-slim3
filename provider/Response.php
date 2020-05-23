<?php
namespace Provider;


define("VALID_RESPONSE_TYPES", json_encode(['guzzle_http_client', 'google_client']));

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use InvalidArgumentException;
use stdClass;
use Provider\Provider;
use Slim\Container;
use Services\Utils;
use SimpleXMLElement;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Google_Client;

abstract class Response {
    protected $response_handling, $response_type;
    function __construct(string $response_type, stdClass $response_handling = null){
        if(!in_array($response_type, json_decode(VALID_RESPONSE_TYPES, true))) throw new InvalidArgumentException("The response type must be one of " . VALID_RESPONSE_TYPES);
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
     * @param Container $container
     * @param Provider $provider
     * @param stdClass $request_input
     * @return GuzzleRequest
     */
    protected function getRequest(Container $container, Provider $provider, stdClass $request_input = null):GuzzleRequest{
        // get the guzzle request for all operations in a unified manner
        $request = $provider->getRequest($request_input);

        //log the request as a curl command for debugging
        $curl_command = (new CurlFormatter())->format($request, []);
        Utils::logArrayContent(['curl_command' => $curl_command], $container['http_logger'], 'debug');

        return $request;
    }
    /**
     * Process a request using the Guzzle client in the dependencies and return a response 
     *
     * @param Container $container
     * @param GuzzleRequest $request
     * @return GuzzleResponse
     */
    private function getGuzzleResponse(Container $container, GuzzleRequest $request):GuzzleResponse{
        $response = $container['external_request_handler']($request);
        return $response;
    }
    /**
     * Process a request using the google client and return a response
     *
     * @param GuzzleRequest $request
     * @param Google_Client $client
     * @return ResponseInterface
     */
    private function getGoogleResponse(GuzzleRequest $request, Google_Client $client):ResponseInterface{
        $response =  $client->execute($request);
        return $response;
    }
    /**
     * Get the response based on the response type
     *
     * @param Container $container
     * @param GuzzleRequest $request
     * @param Google_Client $client
     * @return ResponseInterface
     */
    protected function getResponse(Container $container, GuzzleRequest $request = null, Google_Client $client = null):ResponseInterface{
        $response_types = json_decode("VALID_RESPONSE_TYPES", true);
        if($this->response_type == $response_types[0])
            $response = $this->getGuzzleResponse($container, $request);
        if($this->response_type == $response_types[1])
            $response = $this->getGoogleResponse($request, $client);
        return $response;
    }
    /**
     * Log the response from the Guzzle client
     *
     * @param Container $container
     * @param GuzzleResponse $response
     * @return void
     */
    private function logGuzzleResponse(Container $container, GuzzleResponse $response){
        $logger = $container->get('http_logger');
        Utils::logArrayContent(Utils::getGuzzleResponseInformation($response), $logger, 'info');
    }
    /**
     * Log the response based on the response type
     *
     * @param Container $container
     * @param Response $response
     * @return void
     */
    protected function logResponse(Container $container, ResponseInterface $response){
        $response_types = json_decode("VALID_RESPONSE_TYPES", true);
        if($this->response_type == $response_types[0])
            $this->logGuzzleResponse($container, $response);
        if($this->response_type == $response_types[1])
            $container['response-logger']($response);
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
        if(preg_match("^(<([a-zA-Z0-9]+)([\s]+[a-zA-Z0-9]+="[a-zA-Z0-9]+")*>([^<]*([^<]*|(?1))[^<]*)*<\/\2>)$", $string_response)){
            $xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $string_response);
            $xml = new SimpleXMLElement($xml_response);
            $body = $xml->xpath('//SBody')[0];
            $array = json_decode(json_encode((array)$body), TRUE);
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
    protected function logProviderPublicProps(Provider $provider){
        $log_content = [];
        foreach($this->response_handling->log_additional_class_info as $class_prop){
            if(isset($provider->$class_prop)){
                $log_content[$class_prop] = $provider->$class_prop;
            }
        }
        Utils::logArrayContent($log_content, $container['default_logger'], 'info');
    }
}