## PHP SKELELTON FOR SLIM 3 FRAMEWORK

A PHP Slim 3 Framework skeleton application to assist with rapid REST API development and consumption.

Services Provided
===============
1. Reliable and Extensible Error Handling.
2. Reliable and Extensible Logging.
4. Reliable and Extensible HTTP Middleware.
5. Reliable and Extensible Dependency Injector.
6. PHP Utilities and Auxilliary functionalities.

Installation Instructions
===========================
1. Composer require "opendoorafrica/php-skeleton-for-slim3": "dev-master".
2. Create an ["entry file"](#entry_file) e.g. index.php within the created project.
3. Ensure all HTTP requests to the project dir pass through the entry file by using a .htaccess or nginx location directive.
4. Initalise the skeleton in the entry file.

Skeleton class functionalties
=============
1. Initialise the framework
-------------------
1. Use Skeleton\Framework::init method initialise the framework.
* Provide a callable that takes Slim\App as an argument and has valid routes as specified [here](http://www.slimframework.com/docs/v3/objects/router.html#how-to-create-routes) in the slim documentation.
* Provide an array of callables - that take Slim\Container and Psr\Http\Message\ServerRequestInterface as arguments - to be executed in the Entry Middleware.
2. Initialise the environment variables
-----------------
* APP_ENV : the environment context to run the application in. Mandatory parameter.
* default_log_file : path/to/logs/folder. Optional/Redundant if a handler is provided for logging in the dependencies.

3. Useful abstract and interface classes
-------------------
* The 3rd Party API related code must extend Provider\Provider Abstract class (which implements ProviderInterface). 
* * This class found in the project's Github tree provides useful functionality for handling 3rd Party API calls reliably.

# Sample Skeleon Usage
## <a name="entry_file">Entry file example</a>
```php
define('VALID_API_PATHS', json_encode(['transaction', 'inquirys', 'health']));

require_once 'vendor/autoload.php';

use Slim\Container;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Skeleton\Framework;
use Dotenv\Dotenv;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

$validateRequest = function (Container $container, PsrRequest $request){
    $validation =  $container->get('http_validation');
    $valid_paths = json_decode(VALID_API_PATHS, true);
    $uri = $request->getUri();
    $parameters = [];
    $body = null;
    switch($uri->getPath()){
        case $valid_paths[0]:
            $parameters = [
                'paymentCode' => $validation->setParameterMetadata('numeric', 20),
                'customerId' => $validation->setParameterMetadata('alphanumeric', 50)
            ];
            break;
        break;
        break;
        case $valid_paths[1]:
        case $valid_paths[2];
            return true;
        default:
            throw new Exception("Invalid request path provided: " . $path . ". Valid paths: " . VALID_API_PATHS);
    }
    $validation->setParameters($parameters);
    if(!$body){
        $body = (object) $request->getParsedBody();
    }
    return $validation->validateInput($body);
};
$env_file_path = __DIR__;
$dotenv = Dotenv::createImmutable($env_file_path);
$dotenv->load();
$routes  = function(App $app){
    $app->get('/health', function (Request $request, Response $response, array $args) use ($processRequest) {
        return $processRequest(new APIHealth());
    });
};
Framework::init($routes, [$validateRequest]);