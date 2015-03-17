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
 * Database [[@doctodo class_description:canis\db\mongodb\Database]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Database extends \yii\mongodb\Database
{
    use ComponentTrait;

    /**
     * Get collection names.
     *
     * @return [[@doctodo return_type:getCollectionNames]] [[@doctodo return_description:getCollectionNames]]
     */
    public function getCollectionNames()
    {
        return $this->mongoDb->getCollectionNames();
    }
    /**
     * @inheritdoc
     */
    protected function selectCollection($name)
    {
        return Yii::createObject([
            'class' => Collection::className(),
            'mongoCollection' => $this->mongoDb->selectCollection($name),
        ]);
    }
}
