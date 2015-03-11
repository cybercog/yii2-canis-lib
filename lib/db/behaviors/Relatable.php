<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors;

use teal\caching\Cacher;
use teal\helpers\ArrayHelper;
use Yii;
use yii\base\Event;
use yii\db\Query;

/**
 * Relatable [[@doctodo class_description:teal\db\behaviors\Relatable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Relatable extends \teal\db\behaviors\ActiveRecord
{
    /**
     * @var string Audit event class for relation class
     */
    public $createRelationAuditEventClass = 'teal\db\behaviors\auditable\CreateRelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $deleteRelationAuditEventClass = 'teal\db\behaviors\auditable\DeleteRelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $endRelationAuditEventClass = 'teal\db\behaviors\auditable\EndRelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $updateRelationAuditEventClass = 'teal\db\behaviors\auditable\UpdateRelationEvent';
    /**
     * @var [[@doctodo var_type:parentObjectField]] [[@doctodo var_description:parentObjectField]]
     */
    public $parentObjectField = 'parent_object_id';
    /**
     * @var [[@doctodo var_type:childObjectField]] [[@doctodo var_description:childObjectField]]
     */
    public $childObjectField = 'child_object_id';
    /**
     * @var [[@doctodo var_type:activeField]] [[@doctodo var_description:activeField]]
     */
    public $activeField = 'active';
    /**
     * @var [[@doctodo var_type:startDateField]] [[@doctodo var_description:startDateField]]
     */
    public $startDateField = 'start';
    /**
     * @var [[@doctodo var_type:endDateField]] [[@doctodo var_description:endDateField]]
     */
    public $endDateField = 'end';
    /**
     * @var [[@doctodo var_type:registryModelField]] [[@doctodo var_description:registryModelField]]
     */
    public $registryModelField = 'object_model';
    /**
     * @var [[@doctodo var_type:objectAlias]] [[@doctodo var_description:objectAlias]]
     */
    public $objectAlias = 'o';
    /**
     * @var [[@doctodo var_type:relationAlias]] [[@doctodo var_description:relationAlias]]
     */
    public $relationAlias = 'r';
    /**
     * @var [[@doctodo var_type:registryAlias]] [[@doctodo var_description:registryAlias]]
     */
    public $registryAlias = 'x';

    /**
     * @var [[@doctodo var_type:skipUpdateEvent]] [[@doctodo var_description:skipUpdateEvent]]
     */
    public $skipUpdateEvent = false;

    /**
     * @var [[@doctodo var_type:_relationModels]] [[@doctodo var_description:_relationModels]]
     */
    protected static $_relationModels = [];
    /**
     * @var [[@doctodo var_type:_relationModelsOld]] [[@doctodo var_description:_relationModelsOld]]
     */
    protected static $_relationModelsOld = [];
    /**
     * @var [[@doctodo var_type:_relationsKey]] [[@doctodo var_description:_relationsKey]]
     */
    protected $_relationsKey;
    /**
     * @var [[@doctodo var_type:_relations]] [[@doctodo var_description:_relations]]
     */
    protected $_relations = [];
    /**
     * @var [[@doctodo var_type:_setGlobalEvents]] [[@doctodo var_description:_setGlobalEvents]]
     */
    public static $_setGlobalEvents = false;
    /**
     * @var [[@doctodo var_type:debug]] [[@doctodo var_description:debug]]
     */
    public static $debug = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \teal\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \teal\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
     * Get inherited parent models.
     *
     * @param [[@doctodo param_type:childObject]] $childObject [[@doctodo param_description:childObject]]
     *
     * @return [[@doctodo return_type:getInheritedParentModels]] [[@doctodo return_description:getInheritedParentModels]]
     */
    public function getInheritedParentModels($childObject)
    {
        return [];
    }

    /**
     * Get inherited child models.
     *
     * @param [[@doctodo param_type:parentObject]] $parentObject [[@doctodo param_description:parentObject]]
     *
     * @return [[@doctodo return_type:getInheritedChildModels]] [[@doctodo return_description:getInheritedChildModels]]
     */
    public function getInheritedChildModels($parentObject)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function safeAttributes()
    {
        return ['relationModels'];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $registryClass = Yii::$app->classes['Registry'];
        if (!self::$_setGlobalEvents) {
            self::$_setGlobalEvents = true;
            Event::on(Yii::$app->classes['Registry'], $registryClass::EVENT_BEFORE_DELETE, [$this, 'cleanupRelations']);
        }
    }

    /**
     * [[@doctodo method_description:loadAllParentIds]].
     *
     * @return [[@doctodo return_type:loadAllParentIds]] [[@doctodo return_description:loadAllParentIds]]
     */
    public function loadAllParentIds()
    {
        return self::getAllParentIds($this->owner);
    }

    /**
     * Get all parent ids.
     *
     * @param [[@doctodo param_type:child]] $child [[@doctodo param_description:child]]
     *
     * @return [[@doctodo return_type:getAllParentIds]] [[@doctodo return_description:getAllParentIds]]
     */
    public static function getAllParentIds($child)
    {
        $childObject = null;
        if (is_object($child)) {
            $childObject = $child;
            $child = $child->primaryKey;
        }
        $key = [__CLASS__, 'parentIds', $child];
        $parentIds = Cacher::get($key);
        if ($parentIds) {
            return $parentIds;
        }
        if (is_null($childObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $childObject = $registryClass::getObject($child, false);
        }
        if ($childObject) {
            $parentIds = $childObject->queryParentRelations(false)->select(['parent_object_id'])->column();
            self::setAllParentIds($childObject, $parentIds);

            return $parentIds;
        }

        return [];
    }

    /**
     * Set all parent ids.
     *
     * @param [[@doctodo param_type:child]] $child     [[@doctodo param_description:child]]
     * @param array                         $parentIds [[@doctodo param_description:parentIds]] [optional]
     *
     * @return [[@doctodo return_type:setAllParentIds]] [[@doctodo return_description:setAllParentIds]]
     */
    public static function setAllParentIds($child, $parentIds = [])
    {
        $childObject = null;
        if (is_object($child)) {
            $childObject = $child;
            $child = $child->primaryKey;
        }
        $key = [__CLASS__, 'parentIds', $child];
        $dependency = self::relationCacheDependency($child);
        Cacher::set($key, $parentIds, 0, $dependency);

        return $dependency;
    }

    // this helps prevent a ton of relation calls later on
    /**
     * [[@doctodo method_description:loadChildParentIds]].
     */
    public function loadChildParentIds()
    {
        $dependencies = [];
        $relationClass = Yii::$app->classes['Relation'];
        $relationTable = $relationClass::tableName();
        $key = [__CLASS__, 'parentIdsLoaded', $this->owner->primaryKey];
        $dependencyChain = [];
        $dependencyChain[] = $this->getRelationCacheDependency($this->owner->primaryKey);

        if (!Cacher::get($key)) {
            $query = new Query();
            $subquery = new Query();
            $query->from(['outerRelation' => $relationTable]);
            $this->_prepareRelationQuery($query, 'parents', false, ['alias' => 'outerRelation', 'skipAssociation' => true]);
            $this->_prepareRelationQuery($subquery, 'children', false, ['alias' => 'innerRelation']);
            $subquery->select(['{{innerRelation}}.[[child_object_id]]']);
            $subquery->from(['innerRelation' => $relationTable]);
            // $subquery->andWhere(['{{innerRelation}}.[[parent_object_id]]' => $this->owner->primaryKey]);
            $query->andWhere(['and', '{{outerRelation}}.[[child_object_id]] IN (' . $subquery->createCommand()->rawSql . ')']);
            $query->select(['{{outerRelation}}.[[child_object_id]]', '{{outerRelation}}.[[parent_object_id]]']);
            $childParentsRaw = $query->all();
            $childParents = [];
            foreach ($childParentsRaw as $relation) {
                if (!isset($childParents[$relation['child_object_id']])) {
                    $childParents[$relation['child_object_id']] = [];
                }
                $childParents[$relation['child_object_id']][] = $relation['parent_object_id'];
            }
            foreach ($childParents as $childObjectId => $parentObjectIds) {
                $dependencyChain[] = static::setAllParentIds($childObjectId, $parentObjectIds);
            }
            Cacher::set($key, true, 0, Cacher::chainedDependency($dependencyChain));
        }
    }

    /**
     * [[@doctodo method_description:cleanupRelations]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     *
     * @return [[@doctodo return_type:cleanupRelations]] [[@doctodo return_description:cleanupRelations]]
     */
    public function cleanupRelations($event = null)
    {
        return true;
        // @todo implement
        $relatedModels = $event->sender->relationModels;
        foreach ($relatedModels as $model) {
            $model->delete();
        }
    }

    /**
     * [[@doctodo method_description:handleRelationSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     */
    public function handleRelationSave($event)
    {
        $relationModelKey = $this->relationsKey;
        $registryClass = Yii::$app->classes['Registry'];
        $relationClass = Yii::$app->classes['Relation'];

        if (!empty($this->owner->primaryKey) && !empty(self::$_relationModels[$relationModelKey])) {
            if (!isset(self::$_relationModelsOld[$relationModelKey])) {
                self::$_relationModelsOld[$relationModelKey] = [];
            }
            foreach (self::$_relationModels[$relationModelKey] as $key => $package) {
                unset(self::$_relationModelsOld[$relationModelKey][$key]);
                if (self::$_relationModels[$relationModelKey][$key]['handled']) {
                    continue;
                }
                self::$_relationModels[$relationModelKey][$key]['handled'] = true;
                $model = $package['model'];
                if (!is_object($model)) {
                    continue;
                }
                if (empty($model->{$this->parentObjectField}) && empty($model->{$this->childObjectField})) {
                    continue;
                }
                $parentObject = $childObject = null;

                if (empty($model->{$this->parentObjectField})) {
                    $model->{$this->parentObjectField} = $this->owner->primaryKey;
                } elseif (empty($model->{$this->childObjectField})) {
                    $model->{$this->childObjectField} = $this->owner->primaryKey;
                }

                if ($this->owner->primaryKey === $model->{$this->parentObjectField}) {
                    $parentObject = $this->owner;
                } else {
                    $childObject = $this->owner;
                }
                if (!isset($parentObject)) {
                    $parentObject = $registryClass::getObject($model->{$this->parentObjectField}, false);
                }
                if (!isset($childObject)) {
                    $childObject = $registryClass::getObject($model->{$this->childObjectField}, false);
                }

                if ($model->isNewRecord) {
                    $modelCheck = $relationClass::find()->where(['parent_object_id' => $model->parent_object_id, 'child_object_id' => $model->child_object_id]);
                    $this->addActiveConditions($modelCheck, false);
                    $modelCheck = $modelCheck->one();
                    $dirty = $model->getDirtyAttributes(array_keys($this->defaultRelation));
                    if ($modelCheck) {
                        $newAttributes = $model->attributes;
                        $newAttributes['taxonomy_id'] = $model->taxonomy_id;
                        $dirty[] = 'taxonomy_id';
                        unset($newAttributes['id']);
                        foreach ($newAttributes as $key => $value) {
                            if (!isset($dirty[$key])) {
                                unset($newAttributes[$key]);
                            }
                        }
                        $modelCheck->attributes = $newAttributes;
                        $modelCheck->addDependencies($model->newDependencies);
                        $model = $modelCheck;
                    } else {
                        foreach ($this->defaultRelation as $dkey => $dvalue) {
                            if (!isset($dirty[$dkey])) {
                                $model->{$dkey} = $dvalue;
                            }
                        }
                    }
                }
                $model->loadDefaultValues(true);

                if (!$model->save()) {
                    $event->handled = false;
                }

                $inheritModels = $parentObject->getInheritedParentModels($childObject);
                if (!empty($inheritModels)) {
                    foreach ($inheritModels as $inheritModel) {
                        foreach ($parentObject->parents($inheritModel) as $grandparent) {
                            $modelClone = new $relationClass();
                            $modelClone->attributes = $model->attributes;
                            $modelClone->id = null;
                            $modelClone->clearMemoryId();
                            $modelClone->{$this->parentObjectField} = $grandparent->primaryKey;
                            $modelClone->isNewRecord = true;
                            $modelClone->addDependency($model->primaryKey);
                            $grandparent->registerRelationModel($modelClone);
                            $grandparent->save();
                        }
                    }
                }
            }
            foreach (self::$_relationModelsOld[$relationModelKey] as $relationId) {
                $relationClass = Yii::$app->classes['Relation'];
                $relation = $relationClass::getOne($relationId);
                if ($relation && !$relation->delete()) {
                    $event->handled = false;
                }
            }
            // self::$_relationModels[$relationModelKey] = null;
            self::$_relationModelsOld[$relationModelKey] = null;
        }
    }

    /**
     * [[@doctodo method_description:afterSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     */
    public function afterSave($event)
    {
        $this->handleRelationSave($event);
    }

    /**
     * Get default relation.
     *
     * @return [[@doctodo return_type:getDefaultRelation]] [[@doctodo return_description:getDefaultRelation]]
     */
    public function getDefaultRelation()
    {
        return [
            $this->activeField => 1,
        ];
    }

    /**
     * Get relations key.
     *
     * @return [[@doctodo return_type:getRelationsKey]] [[@doctodo return_description:getRelationsKey]]
     */
    public function getRelationsKey()
    {
        if (is_null($this->_relationsKey)) {
            if (!empty($this->owner->primaryKey)) {
                $this->_relationsKey = $this->owner->primaryKey;
            } else {
                $this->_relationsKey = $this->owner->memoryId;
            }
        }

        return $this->_relationsKey;
    }

    /**
     * Get relation models.
     *
     * @param boolean $activeOnly [[@doctodo param_description:activeOnly]] [optional]
     *
     * @return [[@doctodo return_type:getObjectRelationModels]] [[@doctodo return_description:getObjectRelationModels]]
     */
    public function getObjectRelationModels($activeOnly = false)
    {
        $relationModelKey = $this->relationsKey;
        $relationClass = Yii::$app->classes['Relation'];
        $relationPrimaryKey = $relationClass::primaryKey()[0];
        if (!isset(self::$_relationModels[$relationModelKey])) {
            if ($this->owner->isNewRecord) {
                self::$_relationModels[$relationModelKey] = self::$_relationModelsOld[$relationModelKey] = [];
            } else {
                self::$_relationModels[$relationModelKey] = self::$_relationModelsOld[$relationModelKey] = ArrayHelper::index($this->queryAllRelations(false, ['activeOnly' => $activeOnly]), 'primaryKey');
            }
        }

        return self::$_relationModels[$relationModelKey];
    }

    /**
     * Set relation models.
     *
     * @param [[@doctodo param_type:models]] $models [[@doctodo param_description:models]]
     */
    public function setRelationModels($models)
    {
        $setBase = md5(microtime() . mt_rand());
        foreach ($models as $key => $model) {
            if (is_numeric($key)) {
                $key = $key . '-' . $setBase;
            }
            $this->registerRelationModel($model, $key);
        }
    }

    /**
     * [[@doctodo method_description:registerRelationModel]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param [[@doctodo param_type:key]]   $key   [[@doctodo param_description:key]] [optional]
     *
     * @return [[@doctodo return_type:registerRelationModel]] [[@doctodo return_description:registerRelationModel]]
     */
    public function registerRelationModel($model, $key = null)
    {
        if (is_array($model)) {
            $model['class'] = Yii::$app->classes['Relation'];
            $model = Yii::createObject($model);
        }
        $relationModelKey = $this->relationsKey;
        $id = $model->tabularId;
        if (!isset($_relationModels[$relationModelKey])) {
            $_relationModels[$relationModelKey] = [];
        }
        $idParts = explode(':', $id);

        // if we're working with an existing relation in the database, pull it
        if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->objectRelationModels[$idParts[3]])) {
            $id = $idParts[3];
        }
        $oid = $id;
        if (!is_null($key) && $key !== $id) {
            $id .= '-' . $key;
        }

        if (empty($model->{$this->parentObjectField}) && empty($model->{$this->childObjectField})) {
            return false;
        }

        if (empty($model->{$this->parentObjectField}) && $this->owner->primaryKey !== $model->{$this->childObjectField}) {
            $model->{$this->parentObjectField} = $this->owner->primaryKey;
        } elseif (empty($model->{$this->childObjectField}) && $this->owner->primaryKey !== $model->{$this->parentObjectField}) {
            $model->{$this->childObjectField} = $this->owner->primaryKey;
        }

        self::$_relationModels[$relationModelKey][$id] = ['handled' => false, 'model' => $model];
        $this->registerRelationAuditEvent($model);

        return $model;
    }

    /**
     * [[@doctodo method_description:registerDeleteRelationAuditEvent]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param array                         $base  [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerDeleteRelationAuditEvent]] [[@doctodo return_description:registerDeleteRelationAuditEvent]]
     */
    public function registerDeleteRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->deleteRelationAuditEventClass;
        }

        return $this->registerRelationAuditEvent($model, $base);
    }

    /**
     * [[@doctodo method_description:registerEndRelationAuditEvent]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param array                         $base  [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerEndRelationAuditEvent]] [[@doctodo return_description:registerEndRelationAuditEvent]]
     */
    public function registerEndRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->endRelationAuditEventClass;
        }
        $this->owner->skipUpdateEvent = true;

        return $this->registerRelationAuditEvent($model, $base);
    }

    /**
     * [[@doctodo method_description:registerUpdateRelationAuditEvent]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param array                         $base  [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerUpdateRelationAuditEvent]] [[@doctodo return_description:registerUpdateRelationAuditEvent]]
     */
    public function registerUpdateRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        if ($this->owner->skipUpdateEvent) {
            return false;
        }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->updateRelationAuditEventClass;
        }

        return $this->registerRelationAuditEvent($model, $base);
    }

    /**
     * [[@doctodo method_description:registerCreateRelationAuditEvent]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param array                         $base  [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerCreateRelationAuditEvent]] [[@doctodo return_description:registerCreateRelationAuditEvent]]
     */
    public function registerCreateRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (empty($parentObject) || $parentObject->isNewRecord || $parentObject->getRecentEvent('create')
            || empty($childObject) || $childObject->isNewRecord || $childObject->getRecentEvent('create')) {
            return false;
        }
        if (!isset($base['class'])) {
            $base['class'] = $this->createRelationAuditEventClass;
        }

        return $this->registerRelationAuditEvent($model, $base);
    }

    /**
     * [[@doctodo method_description:registerRelationAuditEvent]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]]
     * @param array                         $base  [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerRelationAuditEvent]] [[@doctodo return_description:registerRelationAuditEvent]]
     */
    protected function registerRelationAuditEvent($model, $base = [])
    {
        $eventLog = $base;
        if (!isset($eventLog['class'])) {
            return false;
        }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        $eventLog['relationObject'] = $model;
        $eventLog['directObject'] = $this->owner;
        if ($this->owner->primaryKey === $parentObject->primaryKey) {
            $eventLog['indirectObject'] = $childObject;
        } else {
            $eventLog['indirectObject'] = $parentObject;
        }
        if ($eventLog['indirectObject']->primaryKey === $eventLog['directObject']->primaryKey) {
            \d($eventLog);
            exit;
        }

        return $this->owner->registerAuditEvent($eventLog);
    }
    /**
     * Get relation model.
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:getObjectRelationModel]] [[@doctodo return_description:getObjectRelationModel]]
     */
    public function getObjectRelationModel($id)
    {
        $relationModelKey = $this->relationsKey;
        if (!isset($_relationModels[$relationModelKey])) {
            $_relationModels[$relationModelKey] = [];
        }

        $idParts = explode(':', $id);

        // if we're working with an existing relation in the database, pull it
        if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->objectRelationModels[$idParts[3]])) {
            $id = $idParts[3];
        }
        // for lazy loading relations
        if (isset($this->objectRelationModels[$id]) && !is_object($this->objectRelationModels[$id]['model'])) {
            $relationClass = Yii::$app->classes['Relation'];
            self::$_relationModels[$relationModelKey][$id] = ['model' => $relationClass::getOne($this->objectRelationModels[$id]), 'handled' => false];
        }

        if (empty(self::$_relationModels[$relationModelKey][$id]['model'])) {
            if (!isset(self::$_relationModels[$relationModelKey][$id])) {
                self::$_relationModels[$relationModelKey][$id] = ['model' => null, 'handled' => false];
            }
            self::$_relationModels[$relationModelKey][$id]['model'] = new Yii::$app->classes['Relation']();
            self::$_relationModels[$relationModelKey][$id]['model']->tabularId = $id;
        }

        return $this->objectRelationModels[$id]['model'];
    }

    /**
     * [[@doctodo method_description:parents]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:parents]] [[@doctodo return_description:parents]]
     */
    public function parents($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->all();
    }

    /**
     * [[@doctodo method_description:parent]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:parent]] [[@doctodo return_description:parent]]
     */
    public function parent($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary_parent', SORT_DESC]);

        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->one();
    }

    /**
     * [[@doctodo method_description:children]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:children]] [[@doctodo return_description:children]]
     */
    public function children($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->all();
    }

    /**
     * [[@doctodo method_description:child]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:child]] [[@doctodo return_description:child]]
     */
    public function child($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary_child', SORT_DESC]);

        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->one();
    }

    /**
     * Get parent ids.
     *
     * @param boolean $model           [[@doctodo param_description:model]] [optional]
     * @param array   $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:getParentIds]] [[@doctodo return_description:getParentIds]]
     */
    public function getParentIds($model = false, $relationOptions = [])
    {
        return $this->queryParentRelations($model, $relationOptions)->select('parent_object_id')->column();
    }

    /**
     * Get child ids.
     *
     * @param boolean $model           [[@doctodo param_description:model]] [optional]
     * @param array   $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:getChildIds]] [[@doctodo return_description:getChildIds]]
     */
    public function getChildIds($model = false, $relationOptions = [])
    {
        return $this->queryChildRelations($model, $relationOptions)->select('child_object_id')->column();
    }

    /**
     * [[@doctodo method_description:queryParentObjects]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryParentObjects]] [[@doctodo return_description:queryParentObjects]]
     */
    public function queryParentObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('parents', $model, $relationOptions, $objectOptions);
    }

    /**
     * [[@doctodo method_description:queryChildObjects]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryChildObjects]] [[@doctodo return_description:queryChildObjects]]
     */
    public function queryChildObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('children', $model, $relationOptions, $objectOptions);
    }

    /**
     * [[@doctodo method_description:queryRelativeObjects]].
     *
     * @param [[@doctodo param_type:relationshipType]] $relationshipType [[@doctodo param_description:relationshipType]]
     * @param [[@doctodo param_type:model]]            $model            [[@doctodo param_description:model]]
     * @param array                                    $relationOptions  [[@doctodo param_description:relationOptions]] [optional]
     * @param array                                    $objectOptions    [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryRelativeObjects]] [[@doctodo return_description:queryRelativeObjects]]
     */
    public function queryRelativeObjects($relationshipType, $model, $relationOptions = [], $objectOptions = [])
    {
        if (is_object($model)) {
            $modelClass = get_class($model);
        } else {
            $modelClass = $model;
            $model = new $modelClass();
        }

        $query = $modelClass::find();
        $this->objectAlias = $modelClass::tableName();
        $this->_prepareRelationQuery($query, $relationshipType, $model, $relationOptions);
        $this->_prepareObjectQuery($query, $relationshipType, $model, $objectOptions);

        return $query;
    }

    /**
     * [[@doctodo method_description:queryParentRelations]].
     *
     * @param boolean $model           [[@doctodo param_description:model]] [optional]
     * @param array   $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryParentRelations]] [[@doctodo return_description:queryParentRelations]]
     */
    public function queryParentRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations('parents', $model, $relationOptions);
    }

    /**
     * [[@doctodo method_description:queryChildRelations]].
     *
     * @param boolean $model           [[@doctodo param_description:model]] [optional]
     * @param array   $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryChildRelations]] [[@doctodo return_description:queryChildRelations]]
     */
    public function queryChildRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations('children', $model, $relationOptions);
    }

    /**
     * [[@doctodo method_description:queryAllRelations]].
     *
     * @param boolean $model           [[@doctodo param_description:model]] [optional]
     * @param array   $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array   $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryAllRelations]] [[@doctodo return_description:queryAllRelations]]
     */
    public function queryAllRelations($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelations(false, $model, $relationOptions, $objectOptions);
    }

    /**
     * [[@doctodo method_description:queryRelations]].
     *
     * @param [[@doctodo param_type:relationshipType]] $relationshipType [[@doctodo param_description:relationshipType]]
     * @param boolean                                  $model            [[@doctodo param_description:model]] [optional]
     * @param array                                    $relationOptions  [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:queryRelations]] [[@doctodo return_description:queryRelations]]
     */
    public function queryRelations($relationshipType, $model = false, $relationOptions = [])
    {
        $relationClass = Yii::$app->classes['Relation'];
        $query = $relationClass::find();
        $this->relationAlias = isset($relationOptions['alias']) ? $relationOptions['alias'] : $relationClass::tableName();
        $query->from([$this->relationAlias => $relationClass::tableName()]);
        $this->_prepareRelationQuery($query, $relationshipType, $model, $relationOptions);
        $this->_prepareRegistryModelCheck($query, $relationshipType, $model);

        return $query;
    }

    /**
     * [[@doctodo method_description:siblingObjectQuery]].
     *
     * @param [[@doctodo param_type:parent]] $parent          [[@doctodo param_description:parent]]
     * @param array                          $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                          $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:siblingObjectQuery]] [[@doctodo return_description:siblingObjectQuery]]
     */
    public function siblingObjectQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildObjects($objectClass, $relationOptions, $objectOptions);
    }

    /**
     * [[@doctodo method_description:siblingRelationQuery]].
     *
     * @param [[@doctodo param_type:parent]] $parent          [[@doctodo param_description:parent]]
     * @param array                          $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                          $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:siblingRelationQuery]] [[@doctodo return_description:siblingRelationQuery]]
     */
    public function siblingRelationQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildRelations($objectClass, $relationOptions, $objectOptions);
    }

    /**
     * [[@doctodo method_description:_prepareSiblingOptions]].
     *
     * @param [[@doctodo param_type:relationOptions]] $relationOptions [[@doctodo param_description:relationOptions]]
     */
    protected function _prepareSiblingOptions(&$relationOptions)
    {
        if (!isset($relationOptions['where'])) {
            $relationOptions['where'] = [];
        } else {
            $relationOptions['where'] = ['and', $relationOptions['where']];
        }
        if (!isset($relationOptions['params'])) {
            $relationOptions['params'] = [];
        }
        $objectClass = get_class($this->owner);
        $relationOptions['where'][] =  ['and', '%alias%.' . $this->parentObjectField . ' != :ownerPrimaryKey'];
        $relationOptions['params'][':ownerPrimaryKey'] = $this->owner->primaryKey;
    }

    /**
     * [[@doctodo method_description:hasParent]].
     *
     * @param [[@doctodo param_type:model]] $model           [[@doctodo param_description:model]]
     * @param [[@doctodo param_type:check]] $check           [[@doctodo param_description:check]] [optional]
     * @param array                         $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                         $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:hasParent]] [[@doctodo return_description:hasParent]]
     */
    public function hasParent($model, $check = null, $relationOptions = [], $objectOptions = [])
    {
        return $this->hasAncestor($model, $check, $relationOptions, $objectOptions, 1);
    }

    /**
     * [[@doctodo method_description:hasAncestor]].
     *
     * @param [[@doctodo param_type:model]]     $model           [[@doctodo param_description:model]]
     * @param [[@doctodo param_type:check]]     $check           [[@doctodo param_description:check]] [optional]
     * @param array                             $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                             $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     * @param [[@doctodo param_type:maxLevels]] $maxLevels       [[@doctodo param_description:maxLevels]] [optional]
     *
     * @return [[@doctodo return_type:hasAncestor]] [[@doctodo return_description:hasAncestor]]
     */
    public function hasAncestor($model, $check = null, $relationOptions = [], $objectOptions = [], $maxLevels = null)
    {
        $ancestors = $this->ancestors($model, $relationOptions, $objectOptions, $maxLevels);
        if (is_null($check) && !empty($ancestors)) {
            return true;
        }
        if (is_object($check)) {
            $check = $check->primaryKey;
        }
        foreach ($ancestors as $a) {
            if ($a->primaryKey == $check) {
                return true;
            }
        }

        return false;
    }

    /**
     * [[@doctodo method_description:ancestors]].
     *
     * @param [[@doctodo param_type:model]]     $model           [[@doctodo param_description:model]]
     * @param array                             $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                             $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     * @param [[@doctodo param_type:maxLevels]] $maxLevels       [[@doctodo param_description:maxLevels]] [optional]
     * @param integer                           $currentLevel    [[@doctodo param_description:currentLevel]] [optional]
     *
     * @return [[@doctodo return_type:ancestors]] [[@doctodo return_description:ancestors]]
     */
    public function ancestors($model, $relationOptions = [], $objectOptions = [], $maxLevels = null, $currentLevel = 0)
    {
        $currentLevel++;
        $ancestors = $this->queryParentObjects($model, $relationOptions, $objectOptions)->all();
        if (!is_null($maxLevels) && $currentLevel >= $maxLevels) {
            return $ancestors;
        }
        foreach ($ancestors as $a) {
            $superAncestors = $a->ancestors($model, $relationOptions, $objectOptions, $maxLevels, $currentLevel);
            foreach ($superAncestors as $key => $aa) {
                $ancestors[] = $aa;
            }
        }

        return $ancestors;
    }

    /**
     * [[@doctodo method_description:descendants]].
     *
     * @param [[@doctodo param_type:model]]     $model           [[@doctodo param_description:model]]
     * @param array                             $relationOptions [[@doctodo param_description:relationOptions]] [optional]
     * @param array                             $objectOptions   [[@doctodo param_description:objectOptions]] [optional]
     * @param [[@doctodo param_type:maxLevels]] $maxLevels       [[@doctodo param_description:maxLevels]] [optional]
     * @param integer                           $currentLevel    [[@doctodo param_description:currentLevel]] [optional]
     *
     * @return [[@doctodo return_type:descendants]] [[@doctodo return_description:descendants]]
     */
    public function descendants($model, $relationOptions = [], $objectOptions = [], $maxLevels = null, $currentLevel = 0)
    {
        $currentLevel++;
        $descendants = $this->owner->queryChildObjects($model, $relationOptions, $objectOptions)->all();
        if (!is_null($maxLevels) && $currentLevel >= $maxLevels) {
            return $descendants;
        }
        foreach ($descendants as $a) {
            $superDescendants = $a->descendants($model, $relationOptions, $objectOptions, $maxLevels, $currentLevel);
            foreach ($superDescendants as $key => $aa) {
                $descendants[] = $aa;
            }
        }

        return $descendants;
    }

    /**
     * [[@doctodo method_description:_aliasKeys]].
     *
     * @param [[@doctodo param_type:conditions]] $conditions [[@doctodo param_description:conditions]]
     * @param [[@doctodo param_type:alias]]      $alias      [[@doctodo param_description:alias]]
     *
     * @return [[@doctodo return_type:_aliasKeys]] [[@doctodo return_description:_aliasKeys]]
     */
    protected function _aliasKeys($conditions, $alias)
    {
        // @todo this should be refactored to accomodate all the different types of condition statements
        if (is_array($conditions)) {
            $newConditions = [];
            foreach ($conditions as $k => $v) {
                $newConditions[$this->_aliasKeys($k, $alias)] = $this->_aliasKeys($v, $alias);
            }

            return $newConditions;
        } else {
            return strtr($conditions, ['%alias%' => $alias]);
        }
    }

    /**
     * [[@doctodo method_description:_prepareObjectQuery]].
     *
     * @param yii\db\Query $query            [[@doctodo param_description:query]]
     * @param boolean      $relationshipType [[@doctodo param_description:relationshipType]] [optional]
     * @param boolean      $model            [[@doctodo param_description:model]] [optional]
     * @param array        $objectOptions    [[@doctodo param_description:objectOptions]] [optional]
     *
     * @return [[@doctodo return_type:_prepareObjectQuery]] [[@doctodo return_description:_prepareObjectQuery]]
     */
    protected function _prepareObjectQuery(Query $query, $relationshipType = false, $model = false, $objectOptions = [])
    {
        $relationClass = Yii::$app->classes['Relation'];
        $relationTableAlias = $relationClass::tableName() . ' ' . $this->relationAlias;
        if (!empty($objectOptions['where'])) {
            $query->andWhere($this->_aliasKeys($objectOptions['where'], $this->objectAlias));
            unset($objectOptions['where']);
        }
        if (!empty($objectOptions['params'])) {
            $query->addParams($objectOptions['params']);
            unset($objectOptions['params']);
        }
        $this->_applyOptions($query, $objectOptions);

        return $query;
    }

    /**
     * [[@doctodo method_description:_prepareRegistryModelCheck]].
     *
     * @param [[@doctodo param_type:query]]            $query            [[@doctodo param_description:query]]
     * @param [[@doctodo param_type:relationshipType]] $relationshipType [[@doctodo param_description:relationshipType]]
     * @param [[@doctodo param_type:model]]            $model            [[@doctodo param_description:model]]
     */
    protected function _prepareRegistryModelCheck($query, $relationshipType, $model)
    {
        if ($model) {
            $relationClass = Yii::$app->classes['Relation'];
            $registryClass = Yii::$app->classes['Registry'];
            $registryTableAlias = $registryClass::tableName() . ' ' . $this->registryAlias;
            if ($relationshipType === 'children') {
                $relationKey = $this->relationAlias . '.' . $this->childObjectField;
            } else {
                $relationKey = $this->relationAlias . '.' . $this->parentObjectField;
            }
            if (!is_object($model)) {
                $model = new $model();
            }
            $query->leftJoin($registryTableAlias, $this->registryAlias . '.' . $registryClass::primaryKey()[0] . '=' . $relationKey);
            $query->andWhere([$this->registryAlias . '.' . $this->registryModelField => $model::modelAlias()]);
        }
    }

    /**
     * [[@doctodo method_description:_prepareRelationQuery]].
     *
     * @param yii\db\Query $query            [[@doctodo param_description:query]]
     * @param boolean      $relationshipType [[@doctodo param_description:relationshipType]] [optional]
     * @param boolean      $model            [[@doctodo param_description:model]] [optional]
     * @param array        $relationOptions  [[@doctodo param_description:relationOptions]] [optional]
     *
     * @return [[@doctodo return_type:_prepareRelationQuery]] [[@doctodo return_description:_prepareRelationQuery]]
     */
    protected function _prepareRelationQuery(Query $query, $relationshipType = false, $model = false, $relationOptions = [])
    {
        $activeOnly = !isset($relationOptions['activeOnly']) || $relationOptions['activeOnly'];
        $taxonomy = false;
        if (!empty($relationOptions['taxonomy'])) {
            $taxonomy = $relationOptions['taxonomy'];
            if (empty($taxonomy)) {
                $taxonomy = [0];
            }
        }
        $skipAssociation = isset($relationOptions['skipAssociation']) && $relationOptions['skipAssociation'];
        $relationClass = Yii::$app->classes['Relation'];
        $relationAlias = isset($relationOptions['alias']) ? $relationOptions['alias'] : $this->relationAlias;
        $relationTableAlias = $relationClass::tableName() . ' ' . $relationAlias;
        $conditions = [];
        $conditions[] = 'and';
        $params = isset($relationOptions['params']) ? $relationOptions['params'] : [];
        $activeConditions = isset($relationOptions['where']) ? $relationOptions['where'] : [];
        unset($relationOptions['where']);
        unset($relationOptions['params']);
        unset($relationOptions['activeOnly']);

        $modelClass = false;
        if (isset($model) && $model) {
            if (is_object($model)) {
                $modelClass = get_class($model);
            } else {
                $modelClass = $model;
                $model = new $modelClass();
            }
            $modelPrimaryKey = $modelClass::primaryKey()[0];
        }
        $modelPrefix = false;
        if ($modelClass) {
            $modelPrefix = $modelClass::modelPrefix();
        }

        $relationQuery = (isset($query->modelClass) && $query->modelClass === Yii::$app->classes['Relation']) || !$model;

        if ($relationQuery) {
            $conditionsDestination = 'where';
        } else {
            $conditionsDestination = 'on';
        }

        if (!$skipAssociation) {
            if ($relationshipType === 'parents') {
                $primaryKey = $this->parentObjectField;
                $foreignKey = $this->childObjectField;
            } elseif ($relationshipType === 'children') {
                $primaryKey = $this->childObjectField;
                $foreignKey = $this->parentObjectField;
            } else {
                $conditions[] = [
                    'or',
                    ['{{' . $relationAlias . '}}.[[' . $this->parentObjectField . ']]' => $this->owner->primaryKey],
                    ['{{' . $relationAlias . '}}.[[' . $this->childObjectField . ']]' => $this->owner->primaryKey],
                ];
            }

            if (!$relationQuery && isset($primaryKey)) {
                if ($modelPrefix) {
                    $modelPrefix .= '-';
                    $modelPrefixLength = strlen($modelPrefix);
                    $conditions[] = ['LEFT({{' . $relationAlias . '}}.[[' . $primaryKey . ']], ' . $modelPrefixLength . ')' => $modelPrefix];
                    //$conditions[] = '{{'. $relationAlias .'}}.[['. $primaryKey .']] LIKE "'. $modelPrefix.'%"';
                }
                $conditions[] = '{{' . $relationAlias . '}}.[[' . $primaryKey . ']] = {{' . $this->objectAlias . '}}.[[' . $modelPrimaryKey . ']]';
            }
        }

        if (isset($foreignKey)) {
            $query->andWhere(['{{' . $relationAlias . '}}.[[' . $foreignKey . ']]' => $this->owner->primaryKey]);
        }

        if ($activeOnly) {
            $isActiveCondition = [$relationAlias . '.' . $this->activeField => 1];
            if (isset($activeConditions[$this->activeField])) {
                $isActiveCondition = $activeConditions[$this->activeField];
                unset($activeConditions[$this->activeField]);
            }
            $startDateCondition = ['or', '{{' . $relationAlias . '}}.[[' . $this->startDateField . ']] IS NULL', '{{' . $relationAlias . '}}.[[' . $this->startDateField . ']] <= CURDATE()'];
            if (isset($activeConditions[$this->startDateField])) {
                $startDateCondition = $activeConditions[$this->startDateField];
                unset($activeConditions[$this->startDateField]);
            }
            $endDateCondition = ['or', '{{' . $relationAlias . '}}.[[' . $this->endDateField . ']] IS NULL', '{{' . $relationAlias . '}}.[[' . $this->endDateField . ']] >= CURDATE()'];
            if (isset($activeConditions[$this->endDateField])) {
                $endDateCondition = $activeConditions[$this->endDateField];
                unset($activeConditions[$this->endDateField]);
            }
            $parts = ['isActive', 'endDate', 'startDate'];
            foreach ($parts as $part) {
                $var = $part . 'Condition';
                if (isset($$var) && $$var) {
                    $conditions[] = $this->_aliasKeys($$var, $relationAlias);
                }
            }
        }
        $debug = false;

        if (!empty($activeConditions)) {
            $activeConditions = $this->_aliasKeys($activeConditions, $relationAlias);
            $conditions[] = $activeConditions;
        }

        if ($conditionsDestination === 'on') {
            $query->leftJoin($relationTableAlias, $conditions, $params);
        } else {
            $query->addParams($params);
            $query->andWhere($conditions);
        }
        if ($taxonomy) {
            if ($relationQuery) {
                $query->filterByTaxonomy($taxonomy, ['queryAlias' => $relationAlias]);
            } elseif ($query->getBehavior('Taxonomy') !== null) {
                $original = ['viaModelClass' => $query->viaModelClass, 'relationKey' => $query->relationKey, 'taxonomyKey' => $query->taxonomyKey];
                $relationClass = Yii::$app->classes['Relation'];
                $relation = new $relationClass();
                $relationQuery = $relation::find();
                Yii::configure($query, ['viaModelClass' => $relationQuery->viaModelClass, 'relationKey' => $relationQuery->relationKey, 'taxonomyKey' => $relationQuery->taxonomyKey]);
                $query->filterByTaxonomy($taxonomy, ['queryAlias' => $relationAlias]);
                Yii::configure($query, $original);
            }
        }
        $this->_applyOptions($query, $relationOptions);

        if (!$relationQuery) {
            $relationClass = Yii::$app->classes['Relation'];
            $relationTable = $relationClass::tableName();
            $relationFields = Yii::$app->db->getTableSchema($relationTable)->columnNames;
            foreach ($relationFields as $field) {
                $fieldAlias = '__relationModel*' . $field;
                $query->ensureSelect[$fieldAlias] = '{{' . $relationAlias . '}}.[[' . $field . ']]';
            }
        }

        return $query;
    }

    /**
     * [[@doctodo method_description:addActiveConditions]].
     *
     * @param [[@doctodo param_type:query]] $query [[@doctodo param_description:query]]
     * @param [[@doctodo param_type:alias]] $alias [[@doctodo param_description:alias]] [optional]
     */
    public function addActiveConditions($query, $alias = null)
    {
        self::doAddActiveConditions($query, $alias);
    }

    /**
     * [[@doctodo method_description:doAddActiveConditions]].
     *
     * @param [[@doctodo param_type:query]] $query [[@doctodo param_description:query]]
     * @param [[@doctodo param_type:alias]] $alias [[@doctodo param_description:alias]] [optional]
     */
    public static function doAddActiveConditions($query, $alias = null)
    {
        $instance = new static();
        if (is_null($alias)) {
            $alias = $instance->relationAlias;
        }
        if ($alias === false) {
            $alias = '';
        } else {
            $alias = '{{' . $alias . '}}.';
        }
        $conditions = ['and'];
        $conditions[] = [$alias . '[[' . $instance->activeField . ']]' => 1];
        $conditions[] = ['or', $alias . '[[' . $instance->startDateField . ']] IS NULL', $alias . '[[' . $instance->startDateField . ']] <= CURDATE()'];
        $conditions[] = ['or', $alias . '[[' . $instance->endDateField . ']] IS NULL', $alias . '[[' . $instance->endDateField . ']] >= CURDATE()'];
        $query->andWhere($conditions);
    }

    /**
     * [[@doctodo method_description:_applyOptions]].
     *
     * @param yii\db\Query $query   [[@doctodo param_description:query]]
     * @param array        $options [[@doctodo param_description:options]] [optional]
     *
     * @return [[@doctodo return_type:_applyOptions]] [[@doctodo return_description:_applyOptions]]
     */
    protected function _applyOptions(Query $query, $options = [])
    {
        foreach ($options as $method => $args) {
            if (method_exists($query, $method)) {
                $query->$method($args);
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:isParentPrimary]].
     *
     * @param [[@doctodo param_type:companionId]] $companionId [[@doctodo param_description:companionId]]
     *
     * @return [[@doctodo return_type:isParentPrimary]] [[@doctodo return_description:isParentPrimary]]
     */
    public function isParentPrimary($companionId)
    {
        $key = 'parent-' . $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getObjectRelation($companionId, $this->owner->primaryKey);
            if ($relation) {
                return !empty($relation->primary);
            }
        }

        return false;
    }

    /**
     * [[@doctodo method_description:isChildPrimary]].
     *
     * @param [[@doctodo param_type:companionId]] $companionId [[@doctodo param_description:companionId]]
     *
     * @return [[@doctodo return_type:isChildPrimary]] [[@doctodo return_description:isChildPrimary]]
     */
    public function isChildPrimary($companionId)
    {
        $key = 'child-' . $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getObjectRelation($this->owner->primaryKey, $companionId);
            if ($relation) {
                return !empty($relation->primary);
            }
        }

        return false;
    }

    /**
     * [[@doctodo method_description:parentModel]].
     *
     * @param [[@doctodo param_type:companionId]] $companionId [[@doctodo param_description:companionId]]
     *
     * @return [[@doctodo return_type:parentModel]] [[@doctodo return_description:parentModel]]
     */
    public function parentModel($companionId)
    {
        $key = 'parent-' . $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['model'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getObjectRelation($companionId, $this->owner->primaryKey);
            if ($relation && ($parent = $relation->parentObject)) {
                return get_class($parent);
            }
        }

        return false;
    }

    /**
     * [[@doctodo method_description:childModel]].
     *
     * @param [[@doctodo param_type:companionId]] $companionId [[@doctodo param_description:companionId]]
     *
     * @return [[@doctodo return_type:childModel]] [[@doctodo return_description:childModel]]
     */
    public function childModel($companionId)
    {
        $key = 'child-' . $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getObjectRelation($this->owner->primaryKey, $companionId);
            if ($relation && ($child = $relation->childObject)) {
                return get_class($child);
            }
        }

        return false;
    }

    /**
     * Get relation.
     *
     * @param [[@doctodo param_type:parentObject]] $parentObject [[@doctodo param_description:parentObject]]
     * @param [[@doctodo param_type:childObject]]  $childObject  [[@doctodo param_description:childObject]]
     *
     * @return [[@doctodo return_type:getObjectRelation]] [[@doctodo return_description:getObjectRelation]]
     */
    public function getObjectRelation($parentObject, $childObject)
    {
        // @todo only active!
        if (is_object($parentObject)) {
            $parentObject = $parentObject->primaryKey;
        }
        if (is_object($childObject)) {
            $childObject = $childObject->primaryKey;
        }

        return $relationClass::findOne(['parent_object_id' => $parentObject, 'child_object_id' => $childObject]);
    }

    /**
     * Get relation cache dependency.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @return [[@doctodo return_type:getRelationCacheDependency]] [[@doctodo return_description:getRelationCacheDependency]]
     */
    public function getRelationCacheDependency($object)
    {
        return self::relationCacheDependency($object);
    }

    /**
     * [[@doctodo method_description:relationCacheDependency]].
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @return [[@doctodo return_type:relationCacheDependency]] [[@doctodo return_description:relationCacheDependency]]
     */
    public static function relationCacheDependency($object)
    {
        if (is_object($object)) {
            $object = $object->primaryKey;
        }

        return Cacher::groupDependency(['Object', 'relations', $object], 'relation');
    }
}
