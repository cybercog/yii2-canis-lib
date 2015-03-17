<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\console;

use canis\base\ApplicationTrait;

/**
 * Application [[@doctodo class_description:canis\console\Application]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\console\Application implements \canis\base\ApplicationInterface
{
    use ApplicationTrait;
}
