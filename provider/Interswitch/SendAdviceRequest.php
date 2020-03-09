<?php
namespace Provider\Interswitch;
use stdClass;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;

class SendAdviceRequest extends Interswitch {
    function __construct(string $environment, string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $sanitized_input = null):Request{
        if(is_null($sanitized_input)) throw new InvalidArgumentException("The input parameter cannot be null");
        //handle the urls for the different countries using the existence of certain parameters in the environment
        $base_url = $_ENV['terminal_id'] ? $_ENV['sva_base_url'] : $_ENV['quick_teller_base_url'];
        $endpoint = $_ENV['adviceRequest'] ? $_ENV['adviceRequest'] : $_ENV['payment_advise_url'];
        return $this->getAPIRequest('POST', $base_url, $endpoint, $sanitized_input);
    }
}