<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use yii\base\ModelEvent;
use infinite\base\ComponentTrait;

class ActiveQuery extends \yii\db\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
    /**
     * @event Event an event that is triggered before a query
     */
    const EVENT_BEFORE_QUERY = 'beforeQuery';

    public function init()
    {
        parent::init();
        $modelClass = $this->modelClass;
        if ($modelClass::isAccessControlled()) {
            $this->enableAccessCheck();
        }
    }

    public function getAccessBehaviorConfiguration()
    {
        return [
            'class' => 'infinite\\db\\behaviors\\QueryAccess',
        ];
    }

    public function disableAccessCheck()
    {
        $this->getBehavior('Access') === null || $this->detachBehavior('Access');
        return $this;
    }

    public function enableAccessCheck()
    {
       $this->getBehavior('Access') !== null || $this->attachBehavior('Access', $this->accessBehaviorConfiguration);
       return $this;
    }

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
