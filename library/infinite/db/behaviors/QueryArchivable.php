<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

/**
 * QueryArchivable [[@doctodo class_description:infinite\db\behaviors\QueryArchivable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryArchivable extends QueryBehavior
{
    /**
     * @var [[@doctodo var_type:_allowArchives]] [[@doctodo var_description:_allowArchives]]
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
     * Get allow archives.
     *
     * @return [[@doctodo return_type:getAllowArchives]] [[@doctodo return_description:getAllowArchives]]
     */
    public function getAllowArchives()
    {
        return $this->_allowArchives;
    }

    /**
     * Set allow archives.
     *
     * @return [[@doctodo return_type:setAllowArchives]] [[@doctodo return_description:setAllowArchives]]
     */
    public function setAllowArchives($value)
    {
        $this->_allowArchives = $value;

        return $this->owner;
    }

    /**
     * [[@doctodo method_description:includeArchives]].
     *
     * @return [[@doctodo return_type:includeArchives]] [[@doctodo return_description:includeArchives]]
     */
    public function includeArchives()
    {
        $this->allowArchives = null;

        return $this->owner;
    }

    /**
     * [[@doctodo method_description:onlyArchives]].
     *
     * @return [[@doctodo return_type:onlyArchives]] [[@doctodo return_description:onlyArchives]]
     */
    public function onlyArchives()
    {
        $this->allowArchives = true;

        return $this->owner;
    }

    /**
     * [[@doctodo method_description:excludeArchives]].
     *
     * @return [[@doctodo return_type:excludeArchives]] [[@doctodo return_description:excludeArchives]]
     */
    public function excludeArchives()
    {
        $this->allowArchives = false;

        return $this->owner;
    }

    /**
     * [[@doctodo method_description:beforeQuery]].
     *
     * @return [[@doctodo return_type:beforeQuery]] [[@doctodo return_description:beforeQuery]]
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
            $this->owner->andWhere('{{' . $this->owner->primaryAlias . '}}.[[' . $this->owner->model->getBehavior('Archivable')->archiveField . ']] IS NOT NULL');
        } elseif ($this->allowArchives === false) {
            $this->owner->andWhere('{{' . $this->owner->primaryAlias . '}}.[[' . $this->owner->model->getBehavior('Archivable')->archiveField . ']] IS NULL');
        }

        return true;
    }
}
