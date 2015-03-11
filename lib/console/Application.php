<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\console;

use teal\base\ApplicationTrait;

/**
 * Application [[@doctodo class_description:teal\console\Application]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\console\Application implements \teal\base\ApplicationInterface
{
    use ApplicationTrait;
}
