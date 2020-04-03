<?php
namespace Provider;
use stdClass;
use GuzzleHttp\Psr7\Request;
use Provider\ProviderInterface;

abstract class Provider implements ProviderInterface {
    protected $credentials;
    function __construct(string $environment, string $credentials_dir = ''){
        $this->initialiseEnvironment($environment);
        if(is_null($credentials_dir) || empty($credentials_dir)) $credentials_dir = $_ENV['default_config_path'];
        if(is_readable($credentials_dir)){
            $this->credentials = (object) parse_ini_file($credentials_dir, true, INI_SCANNER_RAW);
        } else throw new \InvalidArgumentException("The credentials path provided is invalid. Path provided: " . $credentials_dir);
    }

    protected function getGuzzleRequest(string $method, string $url, array $headers, string $request_data):Request{
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