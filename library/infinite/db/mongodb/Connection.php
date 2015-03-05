<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use infinite\base\ComponentTrait;
use Yii;

/**
 * Connection [[@doctodo class_description:infinite\db\mongodb\Connection]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Connection extends \yii\mongodb\Connection
{
    use ComponentTrait;

    /**
     * @inheritdoc
     */
    protected function selectDatabase($name)
    {
        $this->open();

        return Yii::createObject([
            'class' => 'infinite\db\mongodb\Database',
            'mongoDb' => $this->mongoClient->selectDB($name),
        ]);
    }
}
