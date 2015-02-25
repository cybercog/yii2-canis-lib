<?php
namespace infinite\action;

use Yii;
use infinite\deferred\models\DeferredAction;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

class NonInteractiveAction extends Action
{
    public function handleInteractions($sleep = 30)
    {
        return false;
    }
    
    public function createInteraction($label, $options, $callback, $handleNow = true)
    {
        return false;
    }
}
?>
