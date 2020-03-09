<?php
namespace Provider\Interswitch;
define('VALID_BILLER_INFORMATION_REQUEST_TYPES', json_encode(['categorys', 'billers', 'items']));
use InvalidArgumentException;
use stdClass;
use GuzzleHttp\Psr7\Request;
use Provider\Interswitch\Interswitch;
class Categorys extends Interswitch {
    function __construct(string $environment, string $credentials_dir = ''){
        parent::__construct($environment ?: 'uganda', $credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        return $this->getAPIRequest('GET', $_ENV['quick_teller_base_url'], $_ENV['get_biller_categories_url']);
    }
}

class Billers extends Interswitch {
    function __construct(string $environment, string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        return $this->getAPIRequest('GET', $_ENV['quick_teller_base_url'], $_ENV['get_billers_url']);
    }
}
class Items extends Interswitch  {
    function __construct(string $environment, string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        if(!isset($input->billerId)) throw new InvalidArgumentException("Biller id must be set in the input. Input provided: " . json_encode($input));
        return $this->getAPIRequest('GET', $_ENV['quick_teller_base_url'], $_ENV['get_billers_url'] . '/'. $input->billerId . '/paymentitems');  
    }
}
class BillerInformation extends Interswitch {
    private $request_type;
    private function validateEnvironment(string $environment){
        if(!isset($environment)) throw new InvalidArgumentException("The environment argument must be provided");
        else return true;
    }
    function __construct(string $request_type, string $environment = '', string $credentials_dir = ''){
        switch($request_type){
            case 'categorys':
                $this->request_type = new Categorys($environment, $credentials_dir);
            break;
            case 'billers':
                $this->validateEnvironment($environment);
                $this->request_type = new Billers($environment, $credentials_dir);
            break;
            case 'payment_items':
                $this->validateEnvironment($environment);
                $this->request_type = new Items($environment, $credentials_dir);
            break;
            default:
                throw new InvalidArgumentException("Invalid request type provided to initialise BillerInformation class. Provided request type: " . $request_type . ". Valid request types: " . VALID_BILLER_INFORMATION_REQUEST_TYPES);
        }
    }
    function getRequest(stdClass $sanitized_input = null):Request{
        // echo get_class($this->request_type);
        return $this->request_type->getRequest($sanitized_input);
    }
}

