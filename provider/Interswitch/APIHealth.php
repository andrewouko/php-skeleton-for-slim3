<?php
namespace Provider\Interswitch;
use stdClass;
use GuzzleHttp\Psr7\Request;
use Provider\Interswitch\Interswitch;
class APIHealth extends Interswitch{
    function __construct(string $environment = 'kenya', string $credentials_dir = ''){
        parent::__construct($environment, $credentials_dir);
    }
    function getRequest(stdClass $input = null):Request{
        return $this->getAPIRequest('GET', $_ENV['quick_teller_base_url'], $_ENV['status_check_url']);
    }
}