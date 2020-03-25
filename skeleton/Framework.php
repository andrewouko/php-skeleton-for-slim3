<?php
namespace Skeleton;
define('SRC_DIRECTORY', __DIR__ . '/../src');
use Slim\App;
use Dotenv\Dotenv;
class Framework {
    public $settings, $app;
    private function __construct(callable $routes, callable $validateRequest, string $env_file_path, array $settings = [], callable $dependencies = null, callable $middleware = null){
        // var_dump($env_file_path);
        $dotenv = Dotenv::createImmutable($env_file_path);
        $dotenv->load();
        if(empty($settings)){
            $settings = require_once SRC_DIRECTORY . '/settings.php';
        }
        $this->settings = $settings;
        $this->app = new App($this->settings);
        if(!$dependencies){
            $dependencies = require_once SRC_DIRECTORY . '/dependencies.php';
        }
        $dependencies($this->app);
        if(!$middleware){
            $middleware = require_once SRC_DIRECTORY . '/middleware.php';
        }
        $middleware($this->app, $validateRequest);
        $routes($this->app);
    }
    static function init(callable $routes, callable $validateRequest, string $env_file_path){
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

        $instance = new Self($routes, $validateRequest, $env_file_path);
        $instance->app->run();
        return $instance;
    }
}