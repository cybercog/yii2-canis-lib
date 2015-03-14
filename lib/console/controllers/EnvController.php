<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\console\controllers;

use teal\base\exceptions\Exception;
use Yii;

/**
 * MigrateController [[@doctodo class_description:teal\console\controllers\MigrateController]].
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
        $env['debug'] = 'true';
        $env['traceLevel'] = '3';
        $env['version'] = '0.0.1';
        $env['app'] = [];
        $env['app']['id'] = '';
        $env['app']['name'] = '';
        
        $env['db'] = [];
        $env['db']['username'] = '';
        $env['db']['password'] = '';
        $env['db']['host'] = '';
        $env['db']['port'] = '';
        $env['db']['dbname'] = '';

	}
}