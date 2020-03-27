# PHP SKELELTON FOR SLIM 3 FRAMEWORK

A PHP Slim 3 Framework skeleton application to assist with rapid REST API development and consumption.

## Installation Instructions
1. Composer require "opendoorafrica/php-skeleton-for-slim3": "dev-master".
2. Create an entry file e.g. index.php within the created project.
3. Ensure all HTTP requests to the project dir pass through the entry file by using a .htaccess or nginx location directive.
4. Initalise the skeleton in the entry file.

Skeleton class functionalties
=============
1. Initialise the framework
-------------------
1.1 Use Skeleton\Framework::init method initialise the framework
1.1.1 Provide a callable that takes Slim\App as an argument and has valid routes as specified here in the slim documentation http://www.slimframework.com/docs/v3/objects/router.html#how-to-create-routes
1.1.2 Provide an array of callables - that take Slim\Container and Psr\Http\Message\ServerRequestInterface as arguments - to be executed in the Entry Middleware
1.2 Initialise the environment variables
1.2.1 default_log_file -> path/to/logs/folder. Optional/Redundant if a handler is provided for logging in the dependencies.
1.2.2 APP_ENV -> the environment context to run the application in. Mandatory parameter.

2. Useful abstract and interface classes
-------------------
2.1 The API related code must extend Provider\Provider Abstract class (which implements ProviderInterface). 
2.1.1 This class found in the project's Github tree provides useful functionality for handling 3rd Party API calls reliably.
 calls reliably.