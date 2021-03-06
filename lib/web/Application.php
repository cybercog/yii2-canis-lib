<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web;

use canis\base\ApplicationTrait;

/**
 * Application [[@doctodo class_description:canis\web\Application]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\web\Application implements \canis\base\ApplicationInterface
{
    use ApplicationTrait;
}
