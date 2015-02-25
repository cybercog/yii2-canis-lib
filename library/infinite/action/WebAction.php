<?php
namespace infinite\action;

use Yii;
use infinite\deferred\models\DeferredAction;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

class WebAction extends Action
{
    public function handleInteractions($sleep = 30)
    {
        $this->resolveInteractions();
        while($this->hasInteractions()) {
            sleep($sleep);
            $this->resolveInteractions();
        }
        return true;
    }
}
?>
