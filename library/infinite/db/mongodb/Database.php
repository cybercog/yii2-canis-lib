<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use Yii;
use infinite\base\ComponentTrait;

class Database extends \yii\mongodb\Database
{
    use ComponentTrait;

    public function getCollectionNames()
    {
        return $this->mongoDb->getCollectionNames();
    }
    protected function selectCollection($name)
    {
        return Yii::createObject([
            'class' => Collection::className(),
            'mongoCollection' => $this->mongoDb->selectCollection($name),
        ]);
    }
}
