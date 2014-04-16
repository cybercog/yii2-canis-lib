<?php
namespace infinite\base;

use Yii;

trait ComponentTrait
{
    public function trigger($name, \yii\base\Event $event = null)
    {
        Yii::trace("Firing ". get_class($this) ."::{$name}");

        return parent::trigger($name, $event);
    }

    public function hasBehavior($name)
    {
        return $this->getBehavior($name) !== null;
    }
}
