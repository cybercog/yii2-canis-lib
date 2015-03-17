<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\mongodb;

use canis\base\ComponentTrait;
use Yii;

/**
 * Connection [[@doctodo class_description:canis\db\mongodb\Connection]].
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
            'class' => 'canis\db\mongodb\Database',
            'mongoDb' => $this->mongoClient->selectDB($name),
        ]);
    }
}
