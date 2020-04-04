<?php
namespace Skeleton;
define('SRC_DIRECTORY', __DIR__ . '/../src');
use Slim\App;
use Dotenv\Dotenv;
use InvalidArgumentException;
use RuntimeException;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;

class Framework {
    public $settings, $app;
    private function __construct(callable $routes, array $settings, callable $dependencies){
        $this->validateSettings($settings);
        $this->settings = $settings;
        $this->app = new App($this->settings);
        $dependencies($this->app);
        $routes($this->app);
    }
    private function validateSettings(array $settings_array){
        if(!array_key_exists('settings', $settings_array)) throw new InvalidArgumentException("The settings key must exist in the settings array");
        $settings = $settings_array['settings'];
        if(!in_array('displayErrorDetails', array_keys($settings)) || !is_bool($settings['displayErrorDetails'])){
            throw new InvalidArgumentException("`displayErrorDetails` setting must be a boolean");
        }
        if(!in_array('log', array_keys($settings)) || !$settings['log'] instanceof HandlerInterface){
            throw new InvalidArgumentException("The `log` setting must be a Monolog\Handler instance.");
        }
    }
    static function init(callable $routes, array $settings = [], array $entry_middleware = [], array $exit_middleware = []){
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
        $default_settings = require_once SRC_DIRECTORY . '/settings.php';
        $settings = array_merge($default_settings['settings'], $settings);
        // get an instance of the framework
        $instance = new Self($routes, $settings, require_once SRC_DIRECTORY . '/dependencies.php');
        //slim\app
        $app = $instance->app;
        //register the middleware
        $middleware = require_once SRC_DIRECTORY . '/middleware.php';
        $middleware($app, $entry_middleware, $exit_middleware);

        $app->run();
        return $instance;
    }
}