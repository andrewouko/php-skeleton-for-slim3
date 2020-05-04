<?php
namespace Provider;

use Exception;
use Slim\Http\Request;
use InvalidArgumentException;
use stdClass;
use Provider\Provider;
use RuntimeException;
use Slim\Container;
use Services\Utils;
use SimpleXMLElement;
use Namshi\Cuzzle\Formatter\CurlFormatter;

class ResponseHandler {
    private $request_input, $response_handling, $provider, $slim_request;
    function __construct(callable $initProvider, Request $request, stdClass $response_handling = null, stdClass $request_input = null){
        $this->provider = $initProvider();
        if(!$this->provider instanceof Provider) throw new RuntimeException("The instance returned by the initProvider callable must be an instance of the Provider class");
        $this->slim_request = $request;
        $this->request_input = $this->getRequestInput($request_input);
        $this->response_handling = $response_handling;
        $this->validateResponseHandling();
    }
    private function validateResponseHandling(){
        foreach(['log', 'decode_response', 'return_request', 'log_additional_class_info'] as $param){
            if(isset($this->response_handling->$param)){
                switch($param){
                    case 'log':
                    case 'decode_response':
                    case 'return_request':
                        if(!is_bool($this->response_handling->$param)) throw new InvalidArgumentException($param . " must be a boolean value. Provided: " . gettype($this->response_handling->$param) . ' Value: ' . $this->response_handling->$param);
                        break;
                    case 'log_additional_class_info':
                        if(!is_array($this->response_handling->$param)) throw new InvalidArgumentException($param . " must be an array. Provided: " . gettype($this->response_handling->$param));
                        break;
                    default:
                        continue;
                }
            }
        }
    }
    private function getRequestInput($request_input){
        if(!$request_input){
            if($this->slim_request->getMethod() == 'POST'){
                $request_input = (object) $this->slim_request->getParsedBody();
            } else {
                $request_input = (object) $this->slim_request->getQueryParams();   
            }
        }
        return $request_input;
    }
    function getResponse(Container $container){
        // get the guzzle request for all operations in a unified manner
        $request = $this->provider->getRequest($this->request_input);

        //log the request as a curl command for debugging
        echo (new CurlFormatter())->format($request, []);


        // handle the response according the mechanisms specified
        if(isset($this->response_handling->return_request) && $this->response_handling->return_request == true)
            return $request;

        //get the response
        $response = $container['external_request_handler']($request);
        if(isset($this->response_handling->log) && $this->response_handling->log == true){
            $container['response-logger']($response);
        }

        //response decoding
        if(isset($this->response_handling->decode_response) && $this->response_handling->decode_response == true){
            $string_response = (string) $response->getBody();

            //handler for json responses
            $json_decoded_response = json_decode($string_response, true);
            if($json_decoded_response) return $json_decoded_response;

            //handler for xml responses
            try{
                $xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $string_response);
                $xml = new SimpleXMLElement($xml_response);
                $body = $xml->xpath('//SBody')[0];
                $array = json_decode(json_encode((array)$body), TRUE);
                return $array;
            } catch(Exception $e){
                echo $e->getMessage();
            }

            //fallback to string
            return $string_response;
        }

        //additional information from the provider class
        if(isset($this->response_handling->log_additional_class_info) && count($this->response_handling->log_additional_class_info)){
            $log_content = [];
            foreach($this->response_handling->log_additional_class_info as $class_prop){
                if(isset($this->provider->$class_prop)){
                    $log_content[$class_prop] = $this->provider->$class_prop;
                }
            }
            Utils::logArrayContent($log_content, $container['default_logger'], 'info');
        }
        return $response;
    }
}