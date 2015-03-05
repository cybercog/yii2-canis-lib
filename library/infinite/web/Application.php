<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use infinite\base\ApplicationTrait;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Application extends \yii\web\Application implements \infinite\base\ApplicationInterface
{
    use ApplicationTrait;
}
