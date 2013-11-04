<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;
use \yii\base\ModelEvent;

class ActiveQuery extends \yii\db\ActiveQuery
{
    use \infinite\base\ComponentTrait;
    /**
     * @event Event an event that is triggered before a query
     */
    const EVENT_BEFORE_QUERY = 'beforeQuery';

    /**
     * Creates a DB command that can be used to execute this query.
     * @param Connection $db the DB connection used to create the DB command.
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @return Command the created DB command instance.
     */
    public function createCommand($db = null)
    {
        $modelEvent = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_QUERY, $modelEvent);
        return parent::createCommand($db);
    }

    public function getIsAco()
    {
        $class = $this->modelClass;
        return $class::$isAco;
    }
}
