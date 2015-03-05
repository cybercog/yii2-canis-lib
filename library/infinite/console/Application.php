<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console;

use infinite\base\ApplicationTrait;

/**
 * Application [[@doctodo class_description:infinite\console\Application]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\console\Application implements \infinite\base\ApplicationInterface
{
    use ApplicationTrait;
}
