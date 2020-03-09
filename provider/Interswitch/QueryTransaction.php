<?php
namespace Provider\Interswitch;
use stdClass;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use Provider\Interswitch\Interswitch;

class QueryTransaction extends Interswitch {
    function __construct(string $environment = 'kenya', string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $sanitized_input = null):Request{
        if(is_null($sanitized_input)) throw new InvalidArgumentException("The input parameter cannot be null");
        // var_dump($sanitized_input);
        return $this->getAPIRequest('GET', $_ENV['quick_teller_base_url'], $_ENV['queryTransaction'] . '?requestReference=' . $sanitized_input->request_ref);
    }
}