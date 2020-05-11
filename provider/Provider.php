<?php
namespace Provider;

use GuzzleHttp\Psr7\MultipartStream;
use stdClass;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use Provider\ProviderInterface;
abstract class Provider implements ProviderInterface {
    protected $credentials;
    function __construct(string $environment){
        $this->initialiseEnvironment($environment);
    }
    protected function initialiseCredentials(string $credentials_dir){
        if(is_readable($credentials_dir)){
            $this->credentials = (object) parse_ini_file($credentials_dir, true, INI_SCANNER_RAW);
        } else throw new \InvalidArgumentException("The credentials path provided is invalid. Path provided: " . $credentials_dir);
    }
    protected function getGuzzleRequest(string $method, string $url, array $headers, $request_data):Request{
        if(!is_string($request_data) || !$request_data instanceof MultipartStream) throw new InvalidArgumentException("The argument passed to the request_data parameter must be of the type string or " . MultipartStream::class . ". Provided: " . gettype($request_data));
        $request_headers = [];
        foreach($headers as $header){
            $h = explode(':', $header);
            $request_headers[$h[0]] = trim($h[1]);
        }
        return new Request($method, $url, $request_headers, $request_data);
    }
    abstract function initialiseEnvironment(string $environment);
    abstract function getRequest(stdClass $sanitized_input = null):Request;
}