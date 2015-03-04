<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use Yii;

trait ComponentTrait
{
    public function trigger($name, \yii\base\Event $event = null)
    {
        Yii::trace("Firing ".get_class($this)."::{$name}");

        return parent::trigger($name, $event);
    }

    public function hasBehavior($name)
    {
        return $this->getBehavior($name) !== null;
    }
}
