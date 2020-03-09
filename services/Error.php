<?php
namespace Services;

use Exception;
use DateTime;
use Psr\Http\Message\ServerRequestInterface as Request;

class Error {
    public $error;
    function __construct(Request $request, Exception $e, string $error_desc = '', string $resolution = '')
    {
        $this->error = $this->formatException($request, $e, $error_desc, $resolution);
    }
    private function formatException(Request $request, Exception $e, string $error_desc, string $resolution){
        $message = ((json_decode($e->getMessage()) == null) ? $e->getMessage() : json_decode($e->getMessage()));
        $formatted_error =  [
            // 'exception' => $e,
            'Desc' => $error_desc,
            'Line' => $e->getLine(),
            'File' => $e->getFile(),
            'Message' => $message,
            'StackTrace' => $e->getTraceAsString(),
            'Code' => $e->getCode(),
            'Exception' => $e->__toString(), 
            'error_time' => new DateTime(),
            'resolution' => empty($resolution) ? 'The logged details of the error can be used to find a resolution.' : $resolution,
            'request_information' => Utils::getRequestInformation($request)
        ];
        if(method_exists($e, 'getResponse')){
            $formatted_error['guzzle_http_response_body'] = (string) $e->getResponse()->getBody();
        }
        // var_dump($formatted_error);
        return $formatted_error;
    }
}