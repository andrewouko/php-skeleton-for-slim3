<?php
namespace Skeleton;
define('SRC_DIRECTORY', __DIR__ . '/../src');
use Slim\App;
use Dotenv\Dotenv;
use InvalidArgumentException;
use RuntimeException;
use Monolog\Handler\HandlerInterface;

class Framework {
    public $settings, $app;
    private function __construct(callable $routes, array $settings = [], array $entry_middleware_callables = [], string $env_file_path = '', callable $dependencies = null, callable $middleware = null){
        $this->initEnvrionment($env_file_path);
        $this->settings = require_once SRC_DIRECTORY . '/settings.php';
        if(in_array('displayErrorDetails', array_keys($settings)) && !is_bool($settings['displayErrorDetails'])){
            throw new InvalidArgumentException("displayErrorDetails setting must be a boolean");
        }
        if(in_array('log', array_keys($settings)) && !$settings['log'] instanceof HandlerInterface){
            throw new InvalidArgumentException("The `log` setting must be a Monolog\Handler instance.");
        }
        $this->settings['settings'] = array_merge($this->settings['settings'], $settings);
        $this->app = new App($this->settings);
        if(!$dependencies){
            $dependencies = require_once SRC_DIRECTORY . '/dependencies.php';
        }
        $dependencies($this->app);
        if(!$middleware){
            $middleware = require_once SRC_DIRECTORY . '/middleware.php';
        }
        $middleware($this->app, $entry_middleware_callables);
        $routes($this->app);
    }
    static function init(callable $routes, array $settings = [], array $entry_middleware_callables = []){
        if (PHP_SAPI == 'cli-server') {
            // To help the built-in PHP dev server, check if the request was actually for
            // something which should probably be served as a static file
            $url  = parse_url($_SERVER['REQUEST_URI']);
            $file = __DIR__ . $url['path'];
            if (is_file($file)) {
                return false;
            }
        }

        session_start();

        $instance = new Self($routes, $settings, $entry_middleware_callables);
        $instance->app->run();
        return $instance;
    }
    private function initEnvrionment(string $env_file_path){
        if(!empty($env_file_path)){
            $dotenv = Dotenv::createImmutable($env_file_path);
            $dotenv->load();
        }
        //check if the application environment context is set
        if(!isset($_ENV['APP_ENV'])) throw new RuntimeException("Unable to determine the application execution context. The APP_ENV environment variable is NOT set.");
    }
}