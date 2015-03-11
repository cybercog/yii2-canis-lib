<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\mongodb;

use teal\base\ComponentTrait;
use Yii;

/**
 * Connection [[@doctodo class_description:teal\db\mongodb\Connection]].
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
            'class' => 'teal\db\mongodb\Database',
            'mongoDb' => $this->mongoClient->selectDB($name),
        ]);
    }
}
