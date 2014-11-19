<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use infinite\base\ComponentTrait;
use Yii;

class Connection extends \yii\mongodb\Connection
{
    use ComponentTrait;

 	protected function selectDatabase($name)
    {
        $this->open();

        return Yii::createObject([
            'class' => 'infinite\db\mongodb\Database',
            'mongoDb' => $this->mongoClient->selectDB($name)
        ]);
    }
}
