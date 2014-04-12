<?php
namespace infinite\db\behaviors;

use Yii;
use infinite\helpers\ArrayHelper;

class QueryArchivable extends QueryBehavior
{
    protected $_allowArchives;

    public function events()
    {
        return [
            \infinite\db\Query::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }

    public function getAllowArchives()
    {
        return $this->_allowArchives;
    }

    public function setAllowArchives($value)
    {
        $this->_allowArchives = $value;
        return $this->owner;
    }

    public function includeArchives()
    {
        $this->allowArchives = null;
        return $this->owner;
    }

    public function onlyArchives()
    {
        $this->allowArchives = true;
        return $this->owner;
    }

    public function excludeArchives()
    {
        $this->allowArchives = false;
        return $this->owner;
    }

    public function beforeQuery($event) {
        if (
            !isset($this->owner->model) 
            || $this->owner->model->getBehavior('Archivable') === null
            || !$this->owner->model->isArchivable()) {
            return true;
        }
        if ($this->allowArchives === true) {
            $this->owner->andWhere('{{'. $this->owner->primaryAlias .'}}.[['. $this->owner->model->getBehavior('Archivable')->archiveField .']] IS NOT NULL');
        } elseif ($this->allowArchives === false) {
            $this->owner->andWhere('{{'. $this->owner->primaryAlias .'}}.[['. $this->owner->model->getBehavior('Archivable')->archiveField .']] IS NULL');
        }
        return true;
    }
}
?>