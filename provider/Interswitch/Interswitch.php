<?php
namespace Provider\Interswitch;
define('VALID_ENVIRONMENTS', json_encode(['kenya','uganda']));
use stdClass;
use GuzzleHttp\Psr7\Request;
use Dotenv\Dotenv;
use Provider\Provider;
use RuntimeException;

abstract class Interswitch extends Provider {
    private static $TIMESTAMP = "TIMESTAMP", $NONCE = "NONCE", $SIGNATURE_METHOD = "signatureMethod", $SIGNATURE = "SIGNATURE",  $AUTHORIZATION = "AUTHORIZATION",  $AUTHORIZATION_REALM = "InterswitchAuth";
    function __construct(string $environment, string $credentials_dir = ''){
        parent::__construct($environment,$credentials_dir);
        // Modify the signature header name if it is a Uganda call
        if(isset($_ENV['terminal_id'])){
            self::$SIGNATURE_METHOD = 'SIGNATURE_METHOD';
        }
    }
    private function generateInterswitchAuth(string $httpMethod, string $resourceUrl, string $clientId, string $clientSecretKey, string $additionalParameters, string $signatureMethod){
        //	$uuid = Uuid::generate()->string;
        //	$nonce =  str_replace("-","",$uuid);

        $timestamp =  self::generateTimestamp();
        $nonce     = self::generateNonce();

        $clientIdBase64 = base64_encode($clientId);
        $authorization = self::$AUTHORIZATION_REALM ." " . $clientIdBase64;
        
        $signature = self::generateSignature($clientId,$clientSecretKey,$resourceUrl,$httpMethod,$timestamp,$nonce,$additionalParameters, $signatureMethod);
        $interswitchAuth =  [self::$AUTHORIZATION => $authorization,
                            self::$TIMESTAMP     => $timestamp,
                            self::$NONCE         => $nonce,
                            self::$SIGNATURE_METHOD => $signatureMethod,
                            self::$SIGNATURE        => $signature
                            ];

        return $interswitchAuth;
    }
    private static function generateSignature($clientId, $clientSecretKey, $resourceUrl, $httpMethod, $timestamp, $nonce, $transactionParams, $signatureMethod) {
        //$resourceUrl = strtolower($resourceUrl);
       // $resourceUrl = str_replace('http://', 'https://', $resourceUrl);
        $encodedUrl = urlencode($resourceUrl);
        $signatureCipher = $httpMethod . '&' . $encodedUrl . '&' . $timestamp . '&' . $nonce . '&' . $clientId . '&' . $clientSecretKey;
        if (!empty($transactionParams) || $transactionParams != "") {
            //$parameters = implode("&", $transactionParams);
            $signatureCipher = $signatureCipher . '&'.$transactionParams;
        }
        // echo "Signature Cipher: ". $signatureCipher . "\n";
        //echo "hash($signatureMethod,$signatureCipher, true):" .  hash($signatureMethod,$signatureCipher, true) . "\n";
        $signature = base64_encode(hash($signatureMethod,$signatureCipher, true));
        // echo "Signature: " . $signature;
        
        return $signature;
    }
    private static function generateNonce() {
        return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    private static function generateTimestamp() {
        $date = new \DateTime(null, new \DateTimeZone("Africa/Lagos"));
        return $date->getTimestamp();
    }
    protected function getAPIRequest(string $request_method, string $base_url, string $endpoint, stdClass $sanitized_input = null):Request
    {
        //remove trailing slash
        if(substr($base_url, strlen($base_url) - 1, strlen($base_url)) == '/'){
            $base_url = substr($base_url, 0, strlen($base_url) - 1);
        }
        //remove starting slash
        if(substr($endpoint, 0, 1) == '/'){
            $endpoint =  substr($endpoint, 1, strlen($endpoint));
        }
        $request_url = $base_url . "/" . $endpoint;
        // echo $request_url;
        $auth = $this->generateInterswitchAuth($request_method, $request_url, $this->credentials->client_id, $this->credentials->client_secret, '', 'SHA256');
        $auth_headers = [];
        foreach($auth as $k => $v){
            $auth_headers[] = $k . ": " . $v;
        }
        $terminal_id = isset($sanitized_input->terminalId) ? $sanitized_input->terminalId : isset($_ENV['terminal_id']) ? $_ENV['terminal_id'] : null;
        $headers = array_merge($auth_headers, ['Content-Type:application/json' , 'Accept-Encoding: gzip', 'Accept-Encoding: deflate', 'Accept-Encoding: br']);
        if(isset($_ENV['terminal_id'])){
            $headers = array_merge($headers, ['TerminalId:' . $terminal_id]);
        }
        $request_headers = [];
        foreach($headers as $header){
            $h = explode(':', $header);
            $request_headers[$h[0]] = trim($h[1]);
        }
        $request = $this->getGuzzleRequest($request_method, $request_url, $request_headers, json_encode($sanitized_input));


        /* if($request_method == 'POST') $response = Utils::curlPost(json_encode($sanitized_input), $request_url, $headers, $additional_response);
        else $response = Utils::curlGet($request_url, json_decode(json_encode($sanitized_input), true), $headers, $additional_response);
        if(!$response['status']) throw new \Exception(json_encode($response)); */
        return $request;
    }
    function initialiseEnvironment(string $environment){
        $globalenv = Dotenv::createImmutable(__DIR__, '.env.global');
        $globalenv->load();
        $globalenv->load();
        $environments = json_decode(VALID_ENVIRONMENTS);
        switch($environment){
            case $environments[0]:
                $dotenv = Dotenv::createImmutable(__DIR__, '.env.kenya');
            break;
            case $environments[1]:
                $dotenv = Dotenv::createImmutable(__DIR__, '.env.uganda');
            break;
            default:
                throw new RuntimeException("Unsupported environment provided: " . $environment);
        }
        if(!$dotenv) throw new RuntimeException("Dotenv must be set at the end of this operation");
        $dotenv->load();
        // var_dump($_ENV);
    }
    abstract function getRequest(stdClass $sanitized_input = null):Request;
    function getRequestReference(int $length  = 12){
        return substr($_ENV['request_reference_prefix'] . md5(uniqid()), 0, $length);
    }
}