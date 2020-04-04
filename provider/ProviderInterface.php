<?php
namespace Provider;
use stdClass;
use GuzzleHttp\Psr7\Request;
interface ProviderInterface {
    //initialise provider using the environment and credentials
    function __construct(string $environment, string $credentials_dir);
    //use named environments to initialise environments for providers
    function initialiseEnvironment(string $environment);
    //providers must return a PSr-7 request object given a certain input
    function getRequest(stdClass $sanitized_input = null):Request;
}