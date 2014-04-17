<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

/**
 * QueryArchivable [@doctodo write class description for QueryArchivable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryArchivable extends QueryBehavior
{
    /**
     * @var __var__allowArchives_type__ __var__allowArchives_description__
     */
    protected $_allowArchives;

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\Query::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }

    /**
     * Get allow archives
     * @return __return_getAllowArchives_type__ __return_getAllowArchives_description__
     */
    public function getAllowArchives()
    {
        return $this->_allowArchives;
    }

    /**
     * Set allow archives
     * @param __param_value_type__ $value __param_value_description__
     * @return __return_setAllowArchives_type__ __return_setAllowArchives_description__
     */
    public function setAllowArchives($value)
    {
        $this->_allowArchives = $value;

        return $this->owner;
    }

    /**
     * __method_includeArchives_description__
     * @return __return_includeArchives_type__ __return_includeArchives_description__
     */
    public function includeArchives()
    {
        $this->allowArchives = null;

        return $this->owner;
    }

    /**
     * __method_onlyArchives_description__
     * @return __return_onlyArchives_type__ __return_onlyArchives_description__
     */
    public function onlyArchives()
    {
        $this->allowArchives = true;

        return $this->owner;
    }

    /**
     * __method_excludeArchives_description__
     * @return __return_excludeArchives_type__ __return_excludeArchives_description__
     */
    public function excludeArchives()
    {
        $this->allowArchives = false;

        return $this->owner;
    }

    /**
     * __method_beforeQuery_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_beforeQuery_type__ __return_beforeQuery_description__
     */
    public function beforeQuery($event)
    {
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
