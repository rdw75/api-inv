<?php

/**
 * Kamu PHP Framework
 * for educational purposes || ready for production
 * 
 * @author dewanakl
 * @see https://github.com/dewanakl/Kamu
 */
class Kernel
{
    /**
     * Object app
     * 
     * @var object $app
     */
    private $app;

    /**
     * Init object
     * 
     * @return void
     */
    function __construct()
    {
        $this->loader();
        $this->app = new \Core\Facades\Application();
        $this->setEnv();
    }

    /**
     * Load all class
     * 
     * @return bool
     */
    private function loader(): bool
    {
        return spl_autoload_register(function (string $name) {
            $name = str_replace('\\', '/', $name);
            $classPath = dirname(__DIR__) . '/' . lcfirst($name) . '.php';

            if (!file_exists($classPath)) {
                throw new \Exception('Class: ' . $name . ' tidak ada !');
            }

            require_once $classPath;
        });
    }

    /**
     * Set env from .env file
     * 
     * @return void
     */
    private function setEnv(): void
    {
        $file = __DIR__ . '/../.env';
        $lines = file_exists($file)
            ? @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
            }
        }
    }

    /**
     * Import helper
     * 
     * @return void
     */
    public function helper(): void
    {
        require_once __DIR__ . '/../helpers/helpers.php';
    }

    /**
     * Get app
     * 
     * @return object
     */
    public function app(): object
    {
        return \Core\Facades\App::new($this->app);
    }

    /**
     * Kernel for web
     * 
     * @return object
     */
    public static function web(): object
    {
        $self = new self();

        define('HTTPS', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || intval($_SERVER['SERVER_PORT']) == 443) ? 'https://' : 'http://');
        define('BASEURL', @$_ENV['BASEURL'] ? rtrim($_ENV['BASEURL'], '/') : HTTPS . $_SERVER['HTTP_HOST']);
        define('DEBUG', (@$_ENV['DEBUG'] == 'true') ? true : false);

        error_reporting(DEBUG ? E_ALL : 0);
        date_default_timezone_set(@$_ENV['TIMEZONE'] ?? 'Asia/Jakarta');

        session_name(@$_ENV['APP_NAME'] ?? 'Kamu');
        session_set_cookie_params([
            'lifetime' => intval(@$_ENV['COOKIE_LIFETIME'] ?? 86400),
            'path' => '/',
            'secure' => (HTTPS == 'https://') ? true : false,
            'httponly' => true,
            'samesite' => 'strict',
        ]);

        require_once __DIR__ . '/../routes/routes.php';
        $self->helper();

        set_exception_handler(function (\Throwable $error) {
            header('Content-Type: text/html');
            if (!DEBUG) {
                unavailable();
            }

            header('HTTP/1.1 500 Internal Server Error');
            show('../helpers/errors/trace', compact('error'));
        });

        return $self->app();
    }

    /**
     * Kernel for console
     * 
     * @return object
     */
    public static function console(): object
    {
        $self = new self();
        $self->helper();
        return $self->app();
    }
}