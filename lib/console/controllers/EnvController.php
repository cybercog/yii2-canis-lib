<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\console\controllers;

use canis\base\exceptions\Exception;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Console;
use yii\helpers\Inflector;
use canis\composer\TwigRender;

/**
 * MigrateController [[@doctodo class_description:canis\console\controllers\MigrateController]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class EnvController extends \yii\console\Controller
{
    public function actionIndex()
    {
        $env = [];

        $env['cookieValidationString'] = static::generateRandomString();
        $env['salt'] = static::generateRandomString();
        $env['debug'] = Console::select("Debug? ", ['true' => 'true', 'false' => 'false']);
        if (empty($env['debug'])) {
            $env['debug'] = 'true';
        }
        $env['traceLevel'] = $env['debug'] === 'true' ? '3' : '0';
        $env['version'] = Console::prompt('Version number: v', ['default' => '0.0.1']);
        $env['app'] = [];
        $namespace = explode('\\', get_class($this));
        array_pop($namespace);
        array_pop($namespace);
        array_pop($namespace);
        $namespace = ucwords(implode(' ', $namespace));
        $env['app']['name'] = Console::prompt('Application name: ', ['default' => $namespace]);
        $env['app']['id'] = Console::prompt('Application id: ', ['default' => Inflector::slug($env['app']['name'])]);

        $env['db'] = [];
        $env['db']['host'] = Console::prompt('Database host: ', ['default' => '127.0.0.1']);
        $env['db']['port'] = Console::prompt('Database port: ', ['default' => '3306']);
        $env['db']['dbname'] = Console::prompt('Database name: ', ['default' => $env['app']['id']]);
        $env['db']['username'] = Console::prompt('Database username: ', ['default' => 'root']);
        $env['db']['password'] = Console::prompt('Database password: ', ['default' => 'root']);

        if (!static::testDatabase($env['db'])) {
            $this->stdout('Database connection failed.'.PHP_EOL, Console::FG_RED, Console::BOLD);
            if (!Console::confirm('Continue with the given database connection information?', false)) {
                $this->stdout('See ya!'.PHP_EOL, Console::FG_YELLOW, Console::BOLD);
                return false;
            }
        }

        if ($this->initEnv($env)) {
            $this->stdout('Environment has been initialized!'.PHP_EOL, Console::FG_CYAN, Console::BOLD);
        } else {
            $this->stdout('Errors occurred while initializing the environemnt'.PHP_EOL, Console::FG_RED, Console::BOLD);
        }
    }

    public function initEnv($env)
    {
        $configDirectory = TEAL_APP_CONFIG_PATH;
        $renderer = new TwigRender();
        $parser = function($file) use ($env, $renderer) {
            $content = file_get_contents($file);
            return $renderer->renderContent($content, $env);
        };

        $findOptions = [];
        $findOptions['only'] = ['*.sample'];
        $files = FileHelper::findFiles($configDirectory, $findOptions);
        foreach ($files as $file) {
            $newFilePath = strtr($file, ['.sample' => '']);
            if ($newFilePath === $file) { continue; }
            $this->stdout($file .'...', Console::FG_CYAN);
            $newContent = $parser($file);
            file_put_contents($newFilePath, $newContent);
            if (!is_file($newFilePath)) {
                $this->stdout('failed!' . PHP_EOL, Console::FG_RED);
                return false;
            }
            $this->stdout('done!' . PHP_EOL, Console::FG_RED);
        }
        return true;
    }

    public static function testDatabase($db)
    {
        try {
            $oe = ini_set('display_errors', 0);
            $dbh = new \PDO('mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';dbname=' . $db['dbname'], $db['username'], $db['password']);
            ini_set('display_errors', $oe);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    protected static function generateRandomString()
    {
        if (!extension_loaded('openssl')) {
            throw new \Exception('The OpenSSL PHP extension is required by Yii2.');
        }
        $length = 32;
        $bytes = openssl_random_pseudo_bytes($length);
        return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
    }

}