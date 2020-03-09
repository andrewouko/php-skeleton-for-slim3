<?php
namespace Provider\Interswitch;
use InvalidArgumentException;
use stdClass;
use GuzzleHttp\Psr7\Request;
use Provider\Provider;
use Provider\ProviderProcessing;
class ValidateCustomer extends Provider implements ProviderProcessing {
    function __construct(string $credentials_dir = ''){
        parent::__construct($credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        if(is_null($input)) throw new InvalidArgumentException("The input parameter cannot be null");
        return $this->getAPIRequest('POST', $_ENV['sva_base_url'], $_ENV['customer_validation_url'], $input);
    }
}