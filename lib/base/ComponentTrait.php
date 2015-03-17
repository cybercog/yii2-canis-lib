<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use Yii;

trait ComponentTrait
{
    public function trigger($name, \yii\base\Event $event = null)
    {
        Yii::trace("Firing " . get_class($this) . "::{$name}");

        return parent::trigger($name, $event);
    }

    public function hasBehavior($name)
    {
        return $this->getBehavior($name) !== null;
    }
}
