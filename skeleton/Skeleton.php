<?php
namespace Skeleton;
define('SRC_DIRECTORY', __DIR__ . '/../src');
use Slim\App;

class Skeleton {
    private $dependencies, $middleware, $routes;
    public $settings, $app;
    private function __construct(string $routes, callable $validateRequest, string $settings = SRC_DIRECTORY . '/settings.php', string $dependencies = SRC_DIRECTORY . '/dependencies.php', string $middleware = SRC_DIRECTORY . '/middleware.php'){
        $this->settings = require_once $settings;

        $this->app = new App($settings);

        $this->dependencies = require_once $dependencies;
        $this->dependencies($this->app);

        $this->middleware = require_once $middleware;
        $this->middleware($this->app, $validateRequest);

        $this->routes = require_once $routes;
        $this->routes($this->app);
    }
    static function init(string $routes, callable $validateRequest){
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

        $instance = new Self($routes, $validateRequest);
        $instance->app->run();
        return $instance;
    }
}