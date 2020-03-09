<?php
namespace Provider\Interswitch;
use InvalidArgumentException;
use stdClass;
use GuzzleHttp\Psr7\Request;
class TransactionsInquirys extends Interswitch {
    function __construct(string $environment = 'uganda', string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        if(is_null($input)) throw new InvalidArgumentException("The input parameter cannot be null");
        $input->requestReference = $this->getRequestReference();
        return $this->getAPIRequest('POST', $_ENV['quick_teller_base_url'], $_ENV['transaction_inquirys'], $input);
    }
}