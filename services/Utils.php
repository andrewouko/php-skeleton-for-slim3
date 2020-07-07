<?php
namespace Services;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use RuntimeException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Slim\Http\Response as SlimResponse;
use stdClass;

final class Utils{
    static function getRequestInformation(Request $request, array $additional_info = []){
        $uri = $request->getUri();
        $body = $request->getBody();
        $files = $request->getUploadedFiles();
        $response = [
            'request_time' => new DateTime(null, new DateTimeZone('Africa/Nairobi')),
            'request_method' => [
                'method' => $request->getMethod(),
                'isGet' => $request->isGet(),
                'isPost' => $request->isPost(),
                'isPut' => $request->isPut(),
                'isDelete' => $request->isDelete(),
                'isHead' => $request->isHead(),
                'isPatch' => $request->isPatch(),
                'isOPtions' => $request->isOptions()
            ],
            'request_uri' => [
                'scheme' => $uri->getScheme(),
                'authority' => $uri->getAuthority(),
                'user_info' => $uri->getUserInfo(),
                'host' => $uri->getHost(),
                'port' => $uri->getPort(),
                'path' => $uri->getPath(),
                'base_url' => $uri->getBaseUrl(),
                'query' => $uri->getQuery(),
                'base_path' => $uri->getBasePath(),
                'fragment' => $uri->getFragment()
            ],
            'request_headers' => $request->getHeaders(),
            'request_body' => [
                'parsed_body' => $request->getParsedBody(),
                'contents' => $body->getContents(),
                'size' => $body->getSize(),
                'seekable' => $body->isSeekable(),
                'writable' => $body->isWritable(),
                'readable' => $body->isReadable(),
                'metadata' => $body->getMetadata()
            ]
        ];
        if($files){
            foreach($files as $key => $file){
                array_merge($response, [
                    'files_' . $key => [
                        'size' => $file->getSize(),
                        'name' => $file->getClientFilename(),
                        'mimeType' => $file->getClientMediaType(),
                        'error' => $file->getError()
                    ]
                ]);
            }
        }
        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            array_merge($response, [
                'isXHR' => true
            ]);
        }
        return (count($additional_info)) ? array_merge($response, $additional_info) : $response;
    }
    static function getServerState(array $additional_info = []){
        $response = [
            '$_SERVER' => $_SERVER,
            '$_REQUEST' => $_REQUEST,
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            'php:\/\/input' => file_get_contents("php://input"),
            '$_FILES' => $_FILES

        ];
        return (count($additional_info)) ? array_merge($response, $additional_info) : $response;   
    }
    static function getResponseInformation(Response $response, array $additional_info = []){
        $body = $response->getBody();
        $response = [
            'response_processing_time' => new DateTime(null, new DateTimeZone('Africa/Nairobi')),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $response->getHeaders(),
            'response_body' => [
                'body' => (string) $body,
                'contents' => $body->getContents(),
                'size' => $body->getSize(),
                'seekable' => $body->isSeekable(),
                'writable' => $body->isWritable(),
                'readable' => $body->isReadable(),
                'metadata' => $body->getMetadata()
            ]
        ];
        return (count($additional_info)) ? array_merge($response, $additional_info) : $response;
    }
    static function getGuzzleRequestInformation(GuzzleRequest $request, array $additional_info = []){
        $uri = $request->getUri();
        $body = $request->getBody();
        $client_prefix = "Guzzle_http_";
        $response = [
            $client_prefix.'request_time' => new \DateTime(null, new DateTimeZone('Africa/Nairobi')),
            $client_prefix.'request_method' => [
                'method' => $request->getMethod(),
            ],
            $client_prefix.'request_uri' => [
                'scheme' => $uri->getScheme(),
                'authority' => $uri->getAuthority(),
                'user_info' => $uri->getUserInfo(),
                'host' => $uri->getHost(),
                'port' => $uri->getPort(),
                'path' => $uri->getPath(),
                'query' => $uri->getQuery(),
            ],
            $client_prefix.'request_headers' => $request->getHeaders(),
            $client_prefix.'request_body' => [
                'contents' => $body->getContents(),
                'size' => $body->getSize(),
                'seekable' => $body->isSeekable(),
                'writable' => $body->isWritable(),
                'readable' => $body->isReadable(),
                'metadata' => $body->getMetadata()
            ]
        ];
        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            array_merge($response, [
                $client_prefix.'isXHR' => true
            ]);
        }
        
        return (count($additional_info)) ? array_merge($response, $additional_info) : $response;
    }
    static function getGuzzleResponseInformation(GuzzleResponse $response, array $additional_info = []){
        $body = $response->getBody();
        $client_prefix = "Guzzle_http_";
        $response = [
            $client_prefix.'response_processing_time' => new DateTime(null, new DateTimeZone('Africa/Nairobi')),
            $client_prefix.'response_status' => $response->getStatusCode(),
            $client_prefix.'reason_phrase' => $response->getReasonPhrase(),
            $client_prefix.'protocol_version' => $response->getProtocolVersion(),
            $client_prefix.'response_headers' => $response->getHeaders(),
            $client_prefix.'response_body' => [
                'body' => (string) $body,
                'contents' => $body->getContents(),
                'size' => $body->getSize(),
                'seekable' => $body->isSeekable(),
                'writable' => $body->isWritable(),
                'readable' => $body->isReadable(),
                'metadata' => $body->getMetadata()
            ]
        ];
        return (count($additional_info)) ? array_merge($response, $additional_info) : $response;
    }
    static function isAbsolutePath(string $path){
        return substr($path, 0, 1) == '/';
    }
    static function validateLogsPath(){
        if(!isset($_ENV['LOGS_PATH'])) throw new \Exception("A valid logs path for the logs must be set");
        if(!self::isAbsolutePath($_ENV['LOGS_PATH'])) throw new \Exception("Logs path must be absolute");
        if(!is_dir($_SERVER['DOCUMENT_ROOT'] . $_ENV['LOGS_PATH'])) throw new \Exception("Logs Path is not valid directory. Ensure it is relative to the web server root");
        if(substr($_ENV['LOGS_PATH'], -1) == '/') throw new \Exception("Logs path cannot end with '/'");
        return true;
    }
    static function setCacheHeaders($file, \DateTime $last_content_mod_dt, $duration = 28, $isPublic = true){
        if(!is_file($file)) throw new \InvalidArgumentException("A valid file is required");
        $file_last_mod_time = filemtime($file);
        $content_last_mod_time = $last_content_mod_dt->getTimestamp();
        $etag = '"' . $file_last_mod_time . '.' . $content_last_mod_time . '"';
        //max age = seconds in a day * duration in days
        $max_age = 86400 * $duration;
        $public = $isPublic ? 'public' : 'private';
        header('Cache-Control: ' . $public . ', max-age='.$max_age);
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $content_last_mod_time));
        header('Etag: '.$etag);
    }
    static function writeToConfig($contents, $append = false){
        $config_file = fopen($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path'], $append ? 'a+' : 'w+') or die("Unable to open/create config file");
        fwrite($config_file, $contents);
        fclose($config_file);
    }
    static function createAPIKey(){
        $isStrong = false; $secure_key = null;
        while(!$isStrong){
            $secure_key = openssl_random_pseudo_bytes(32, $isStrong);
        }
        return $secure_key;
    }
    static function getConfig(){
        try{
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path'])) throw new \Exception("Config file does not exist");
        } catch (\Exception $e){
            if(!is_writable(dirname($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path']))) throw new \Exception("Config file path (" . $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path'] . ") is not writable, hence cannot be created");
            $handle = fopen($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path'], 'w');
            fclose($handle);
            self::setDefaultConfigData();
        }
        $configs = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config_file_path']);
        return $configs;
    }
    private static function setDefaultConfigData(array $data = null){
        if(is_null($data)){
            self::writeToConfig('API_KEY = ' .  '"' . self::createAPIKey() . '"');
        }
    }
    static function utf8_encode_deep(&$input) {
        if (is_string($input)) {
            $input = utf8_encode($input);
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                self::utf8_encode_deep($value);
            }
            
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            
            foreach ($vars as $var) {
                self::utf8_encode_deep($input->$var);
            }
        }
    }
    static function array_to_xml(array $array, &$xml_user_info) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml_user_info->addChild("$key");
                    self::array_to_xml($value, $subnode);
                }else{
                    $subnode = $xml_user_info->addChild("item$key");
                    self::array_to_xml($value, $subnode);
                }
            }else {
                $xml_user_info->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
    static function isJson(string $string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    static function isLocalhost(string $ip_address){
        $whitelist = array(
            '127.0.0.1',
            '::1'
        );
        if(in_array($ip_address, $whitelist)){
            return true;
        }
        return false;
    }
    static function logArrayContent(array $logs, Logger $logger, string $log_level){
        foreach($logs as $title => $info){
            if(is_string($info) || is_bool($info) || is_int($info) || is_float($info)){}else{
                $stringed_info = json_encode($info);
                if(!$stringed_info){
                    // ob_flush();
                    // ob_start();
                    // var_dump($info)
                    if(is_array($info)){
                        $stringed_info = '';
                        foreach($info as $k => $v){
                            if(is_string($k) && (is_string($v) || is_string(json_encode($v)))){
                                if(!is_string($v)) $v = json_encode($v);
                                $stringed_info .= $k . ": " . $v . "\n";
                            }
                        }
                    }
                    if(!$stringed_info || empty($stringed_info))
                        throw new RuntimeException("Unable to convert data to a string. " . gettype($info) . " type found.");
                }
                $info = $stringed_info;
            }
            $log_content =  $title . ": " . $info;
            $logger->$log_level($log_content);
        }
    }
    static function formatJsonResponse(string $data = '', string $error = ''){
        if((empty($data) && empty($error)) || ($data && $error)) throw new InvalidArgumentException("Provide either the error or the data paramter for the json response");
        foreach(['data', 'error'] as $var_name){
            if(self::isJson($$var_name)){
                $$var_name = json_decode($$var_name, true);
            }
        }
        if($data){
            return json_encode(['data' => $data], JSON_UNESCAPED_SLASHES);
        }
        if($error){
            return json_encode(['error' => $error], JSON_UNESCAPED_SLASHES);
        }
    }
    static function getRedirectResponse(string $url, string $message, SlimResponse $response, string $key = 'message'){
        if (filter_var($url, FILTER_VALIDATE_URL) === false) throw new InvalidArgumentException("The url provided for redirect is invalid. URL provided: " . $url);
        $message = urlencode($message);
        // header('Location: '. $url . '?' . $key . '=' . $message);
        return $response->withRedirect($url . '?' . $key . '=' . $message);
    }
    static function generateHash($input, string $hash_key, string $algo, bool $useBuildQuery = true){
        $datastring = '';
        if($input instanceof stdClass){
            $input = json_decode(json_encode($input), true);
            unset($input['hash']);
            ksort($input);
            if($useBuildQuery)
                $datastring = http_build_query($input);
            else
                $datastring = implode('', $input);
        } else if(is_string($input)){
            $datastring = $input;
        } else throw new InvalidArgumentException("The input must be either of stdClass or string. Provided: " . gettype($input));
        return hash_hmac($algo, $datastring , $hash_key);
    }
    static function withAdditionalHeaders(Response $response, array $additional_headers){
        foreach($additional_headers as $header){
            $header = explode(':', $header);
            $header_key = $header[0];
            $header_content = $header[1];
            header_remove($header_key);
            $response = $response->withHeader($header_key, $header_content);
        }
        return $response;
    }
    static function withCORSHeaders(Response $response){
        return self::withAdditionalHeaders($response, [
            'Access-Control-Allow-Origin:*', 
            'Access-Control-Allow-Headers:Content-Type, Accept, Origin, X-Requested-With',
            'Access-Control-Allow-Methods:GET, POST, PUT, DELETE, PATCH, OPTIONS'
        ]); 
    }
    static function getRequestInput(Request $request){
        $request_input = [];
        if($request->getMethod() == 'POST'){
            $request_input = $request->getParsedBody();
        } else {
            $request_input = $request->getQueryParams();   
        }
        $files = $request->getUploadedFiles();
        if($files){
            foreach($files as $field_name => $file){
                $request_input[$field_name] = $file;
            }
        }
        $request_input = (object) $request_input;
        return $request_input;
    }
}
?>