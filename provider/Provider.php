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
    protected function createMultipartStream(array $stream){
        // $stream = [
        //     [
        //         'name' => 'file',
        //         'contents' => fopen($input->file->file, 'r')
        //     ],
        //     [
        //         'name' => 'id_number',
        //         'contents' => $input->id_number
        //     ],
        //     [
        //         'name' => 'channel',
        //         'contents' => $this->credentials->client_name
        //     ]
        // ];
        // $multipart = new MultipartStream($stream);
        return new MultipartStream($stream);
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