<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use infinite\base\ComponentTrait;

class ActiveQuery extends \yii\db\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
    /**
     * @event Event an event that is triggered before a query
     */

    public function getIsAco()
    {
        $class = $this->modelClass;
        return $class::$isAco;
    }
}
