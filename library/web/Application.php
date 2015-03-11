<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

use teal\base\ApplicationTrait;

/**
 * Application [[@doctodo class_description:teal\web\Application]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\web\Application implements \teal\base\ApplicationInterface
{
    use ApplicationTrait;
}
