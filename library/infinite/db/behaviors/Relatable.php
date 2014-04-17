<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;
use yii\db\Query;
use yii\base\Event;

use infinite\helpers\ArrayHelper;
use infinite\caching\Cacher;

/**
 * Relatable [@doctodo write class description for Relatable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Relatable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var string Audit event class for relation class
     */
    public $createRelationAuditEventClass = 'infinite\\db\\behaviors\\auditable\\RelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $deleteRelationAuditEventClass = 'infinite\\db\\behaviors\\auditable\\DeleteRelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $endRelationAuditEventClass = 'infinite\\db\\behaviors\\auditable\\EndRelationEvent';
    /**
     * @var string Audit event class for relation class
     */
    public $updateRelationAuditEventClass = 'infinite\\db\\behaviors\\auditable\\UpdateRelationEvent';
    /**
     * @var __var_parentObjectField_type__ __var_parentObjectField_description__
     */
    public $parentObjectField = 'parent_object_id';
    /**
     * @var __var_childObjectField_type__ __var_childObjectField_description__
     */
    public $childObjectField = 'child_object_id';
    /**
     * @var __var_activeField_type__ __var_activeField_description__
     */
    public $activeField = 'active';
    /**
     * @var __var_startDateField_type__ __var_startDateField_description__
     */
    public $startDateField = 'start';
    /**
     * @var __var_endDateField_type__ __var_endDateField_description__
     */
    public $endDateField = 'end';
    /**
     * @var __var_registryModelField_type__ __var_registryModelField_description__
     */
    public $registryModelField = 'object_model';
    /**
     * @var __var_objectAlias_type__ __var_objectAlias_description__
     */
    public $objectAlias = 'o';
    /**
     * @var __var_relationAlias_type__ __var_relationAlias_description__
     */
    public $relationAlias = 'r';
    /**
     * @var __var_registryAlias_type__ __var_registryAlias_description__
     */
    public $registryAlias = 'x';
    /**
     * @var __var__relationModels_type__ __var__relationModels_description__
     */
    protected static $_relationModels = [];
    /**
     * @var __var__relationModelsOld_type__ __var__relationModelsOld_description__
     */
    protected static $_relationModelsOld = [];
    /**
     * @var __var__relationsKey_type__ __var__relationsKey_description__
     */
    protected $_relationsKey;
    /**
     * @var __var__relations_type__ __var__relations_description__
     */
    protected $_relations = [];
    /**
     * @var __var__setGlobalEvents_type__ __var__setGlobalEvents_description__
     */
    static $_setGlobalEvents = false;
    /**
     * @var __var_debug_type__ __var_debug_description__
     */
    static $debug = [];

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
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
     * __method_loadAllParentIds_description__
     * @return __return_loadAllParentIds_type__ __return_loadAllParentIds_description__
     */
    public function loadAllParentIds()
    {
        return self::getAllParentIds($this->owner);
    }

    /**
     * Get all parent ids
     * @param __param_child_type__ $child __param_child_description__
     * @return __return_getAllParentIds_type__ __return_getAllParentIds_description__
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
     * Set all parent ids
     * @param __param_child_type__ $child __param_child_description__
     * @param array $parentIds __param_parentIds_description__ [optional]
     * @return __return_setAllParentIds_type__ __return_setAllParentIds_description__
     */
    public static function setAllParentIds($child, $parentIds = [])
    {
         $childObject = null;
        if (is_object($child)) {
            $childObject = $child;
            $child = $child->primaryKey;
        }
        $key = [__CLASS__, 'parentIds', $child];
        $dependency = Cacher::groupDependency(['Object', 'relations', $child], 'relation');
        Cacher::set($key, $parentIds, 0, $dependency);

        return $dependency;
    }

    // this helps prevent a ton of relation calls later on
    /**
     * __method_loadChildParentIds_description__
     */
    public function loadChildParentIds()
    {
        $dependencies = [];
        $relationClass = Yii::$app->classes['Relation'];
        $relationTable = $relationClass::tableName();
        $key = [__CLASS__, 'parentIdsLoaded', $this->owner->primaryKey];
        $dependencyChain = [];
        $dependencyChain[] = Cacher::groupDependency(['Object', 'relations', $this->owner->primaryKey]);

        if (!Cacher::get($key)) {
            $query = new Query;
            $subquery = new Query;
            $query->from(['outerRelation' => $relationTable]);
            $this->_prepareRelationQuery($query, 'parents', false, ['alias' => 'outerRelation', 'skipAssociation' => true]);
            $this->_prepareRelationQuery($subquery, 'children', false, ['alias' => 'innerRelation']);
            $subquery->select(['{{innerRelation}}.[[child_object_id]]']);
            $subquery->from(['innerRelation' => $relationTable]);
            // $subquery->andWhere(['{{innerRelation}}.[[parent_object_id]]' => $this->owner->primaryKey]);
            $query->andWhere(['and', '{{outerRelation}}.[[child_object_id]] IN ('. $subquery->createCommand()->rawSql .')']);
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
     * __method_cleanupRelations_description__
     * @param __param_event_type__ $event __param_event_description__ [optional]
     * @return __return_cleanupRelations_type__ __return_cleanupRelations_description__
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
     * __method_handleRelationSave_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function handleRelationSave($event)
    {
        $relationModelKey = $this->relationsKey;
        if (!empty($this->owner->primaryKey) && !empty(self::$_relationModels[$relationModelKey])) {
            if (!isset(self::$_relationModelsOld[$relationModelKey])) {
                self::$_relationModelsOld[$relationModelKey] = [];
            }
            foreach (self::$_relationModels[$relationModelKey] as $key => $package) {
                unset(self::$_relationModelsOld[$relationModelKey][$key]);
                if (self::$_relationModels[$relationModelKey][$key]['handled']) { continue; }
                self::$_relationModels[$relationModelKey][$key]['handled'] = true;
                $model = $package['model'];
                if (!is_object($model)) { continue; }
                if (empty($model->{$this->parentObjectField}) && empty($model->{$this->childObjectField})) {
                    continue;
                }

                if (empty($model->{$this->parentObjectField})) {
                    $model->{$this->parentObjectField} = $this->owner->primaryKey;
                } elseif (empty($model->{$this->childObjectField})) {
                    $model->{$this->childObjectField} = $this->owner->primaryKey;
                }

                if ($model->isNewRecord) {
                    $relationClass = Yii::$app->classes['Relation'];
                    $modelCheck = $relationClass::find()->where(['parent_object_id' => $model->parent_object_id, 'child_object_id' => $model->child_object_id]);
                    
                    $this->addActiveConditions($modelCheck, false);
                    $modelCheck = $modelCheck->one();


                    $dirty = $model->getDirtyAttributes(array_keys($this->defaultRelation));
                    if ($modelCheck) {
                        $newAttributes = $model->attributes;
                        unset($newAttributes['id']);
                        foreach ($newAttributes as $key => $value) {
                            if (!isset($dirty[$key])) {
                                unset($newAttributes[$key]);
                            }
                        }
                        $modelCheck->attributes = $newAttributes;
                        $model = $modelCheck;
                    } else {
                        foreach ($this->defaultRelation as $dkey => $dvalue) {
                            if (!isset($dirty[$dkey])) {
                                $model->{$dkey} = $dvalue;
                            }
                        }
                    }
                }

                if (!$model->save()) {
                    $event->handled = false;
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
     * __method_afterSave_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function afterSave($event)
    {
        $this->handleRelationSave($event);
    }

    /**
     * Get default relation
     * @return __return_getDefaultRelation_type__ __return_getDefaultRelation_description__
     */
    public function getDefaultRelation()
    {
        return [
            $this->activeField => 1
        ];
    }

    /**
     * Get relations key
     * @return __return_getRelationsKey_type__ __return_getRelationsKey_description__
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
     * Get relation models
     * @param boolean $activeOnly __param_activeOnly_description__ [optional]
     * @return __return_getRelationModels_type__ __return_getRelationModels_description__
     */
    public function getRelationModels($activeOnly = false)
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
     * Set relation models
     * @param __param_models_type__ $models __param_models_description__
     */
    public function setRelationModels($models)
    {
        $setBase = md5(microtime(true));
        foreach ($models as $key => $model) {
            if (is_numeric($key)) {
                $key = $key .'-'. $setBase;
            }
            $this->registerRelationModel($model, $key);
        }
    }

    /**
     * __method_registerRelationModel_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param __param_key_type__ $key __param_key_description__ [optional]
     * @return __return_registerRelationModel_type__ __return_registerRelationModel_description__
     */
    public function registerRelationModel($model, $key = null)
    {
        if (is_array($model)) {
            $model['class'] = Yii::$app->classes['Relation'];
            $model = Yii::createObject($model);
        }
        $relationModelKey = $this->relationsKey;
        $id = $model->tabularId;
        if (!isset($_relationModels[$relationModelKey])) { $_relationModels[$relationModelKey] = []; }
        $idParts = explode(':', $id);

        // if we're working with an existing relation in the database, pull it
        if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->relationModels[$idParts[3]])) {
            $id = $idParts[3];
        }
        $oid = $id;
        if (!is_null($key) && $key !== $id) {
            $id .= '-'. $key;
        }

        if (empty($model->{$this->parentObjectField}) && empty($model->{$this->childObjectField})) {
            return false;
        }

        if (empty($model->{$this->parentObjectField})) {
            $model->{$this->parentObjectField} = $this->owner->primaryKey;
        } elseif (empty($model->{$this->childObjectField})) {
            $model->{$this->childObjectField} = $this->owner->primaryKey;
        }

        self::$_relationModels[$relationModelKey][$id] = ['handled' => false, 'model' => $model];
        $this->registerRelationAuditEvent($model);
        return $model;
    }

    public function registerDeleteRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) { return false; }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->deleteRelationAuditEventClass;
        }
        return $this->registerRelationAuditEvent($model, $base);
    }

    public function registerEndRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) { return false; }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->endRelationAuditEventClass;
        }
        return $this->registerRelationAuditEvent($model, $base);
    }

    public function registerUpdateRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) { return false; }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!isset($base['class'])) {
            $base['class'] = $this->updateRelationAuditEventClass;
        }
        return $this->registerRelationAuditEvent($model, $base);
    }

    public function registerCreateRelationAuditEvent($model, $base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) { return false; }
        $parentObject = $model->getParentObject(false);
        $childObject = $model->getChildObject(false);
        if (!$model->isNewRecord ||
            empty($parentObject) || $parentObject->isNewRecord 
            || empty($childObject) || $childObject->isNewRecord) {
            return false;
        }
        if (!isset($base['class'])) {
            $base['class'] = $this->createRelationAuditEventClass;
        }
        return $this->registerRelationAuditEvent($model, $base);
    }

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
        if ($this->owner === $parentObject) {
            $eventLog['indirectObject'] = $childObject;
        } else {
            $eventLog['indirectObject'] = $parentObject;
        }
        return $this->owner->registerAuditEvent($eventLog);   
    }
    /**
     * Get relation model
     * @param __param_id_type__ $id __param_id_description__
     * @return __return_getRelationModel_type__ __return_getRelationModel_description__
     */
    public function getRelationModel($id)
    {
        $relationModelKey = $this->relationsKey;
        if (!isset($_relationModels[$relationModelKey])) { $_relationModels[$relationModelKey] = []; }

        $idParts = explode(':', $id);

        // if we're working with an existing relation in the database, pull it
        if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->relationModels[$idParts[3]])) {
            $id = $idParts[3];
        }
        // for lazy loading relations
        if (isset($this->relationModels[$id]) && !is_object($this->relationModels[$id]['model'])) {
            $relationClass = Yii::$app->classes['Relation'];
            self::$_relationModels[$relationModelKey][$id] = ['model' => $relationClass::getOne($this->relationModels[$id]), 'handled' => false];
        }

        if (empty(self::$_relationModels[$relationModelKey][$id]['model'])) {
            if (!isset(self::$_relationModels[$relationModelKey][$id])) {
                self::$_relationModels[$relationModelKey][$id] = ['model' => null, 'handled' => false];
            }
            self::$_relationModels[$relationModelKey][$id]['model'] = new Yii::$app->classes['Relation'];
            self::$_relationModels[$relationModelKey][$id]['model']->tabularId = $id;
        }

        return $this->relationModels[$id]['model'];
    }

    /**
     * __method_parents_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_parents_type__ __return_parents_description__
     */
    public function parents($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->all();
    }

    /**
     * __method_parent_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_parent_type__ __return_parent_description__
     */
    public function parent($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary', SORT_DESC]);

        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->one();
    }

    /**
     * __method_children_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_children_type__ __return_children_description__
     */
    public function children($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->all();
    }

    /**
     * __method_child_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_child_type__ __return_child_description__
     */
    public function child($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary', SORT_DESC]);

        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->one();
    }

    /**
     * Get parent ids
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_getParentIds_type__ __return_getParentIds_description__
     */
    public function getParentIds($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryParentRelations($model, $relationOptions, $objectOptions)->select('parent_object_id')->column();
    }

    /**
     * Get child ids
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_getChildIds_type__ __return_getChildIds_description__
     */
    public function getChildIds($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryChildRelations($model, $relationOptions, $objectOptions)->select('child_object_id')->column();
    }

    /**
     * __method_queryParentObjects_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_queryParentObjects_type__ __return_queryParentObjects_description__
     */
    public function queryParentObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('parents', $model, $relationOptions, $objectOptions);
    }

    /**
     * __method_queryChildObjects_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_queryChildObjects_type__ __return_queryChildObjects_description__
     */
    public function queryChildObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('children', $model, $relationOptions, $objectOptions);
    }

    /**
     * __method_queryRelativeObjects_description__
     * @param __param_relationshipType_type__ $relationshipType __param_relationshipType_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_queryRelativeObjects_type__ __return_queryRelativeObjects_description__
     */
    public function queryRelativeObjects($relationshipType, $model, $relationOptions = [], $objectOptions = [])
    {
        if (is_object($model)) {
            $modelClass = get_class($model);
        } else {
            $modelClass = $model;
            $model = new $modelClass;
        }

        $query = $modelClass::find();
        // $query->select = [
        //     $query->primaryAlias .'.*',
        //     $this->relationAlias .'.id as `r.id`',
        // //    $this->relationAlias .'.start as `r.start`',
        // //    $this->relationAlias .'.end as `r.end`',
        //     $this->relationAlias .'.primary as `r.primary`',
        // //    $this->relationAlias .'.special as `r.special`',
        // ];
        // if (in_array($relationshipType, ['children', 'child'])) {
        //     $query->select[] = '\''. addslashes(get_class($this->owner)) . '\' as `r.cm`';
        //     $query->select[] = '\'parent\' as `r.ct`';
        //     $query->select[] = $this->relationAlias .'.parent_object_id as `r.cid`';
        // } else {
        //     $query->select[] = '\''. addslashes(get_class($this->owner)) . '\' as `r.cm`';
        //     $query->select[] = '\'child\' as `r.ct`';
        //     $query->select[] = $this->relationAlias .'.child_object_id as `r.cid`';
        // }
        $this->objectAlias = $modelClass::tableName();
        $this->_prepareRelationQuery($query, $relationshipType, $model, $relationOptions);
        $this->_prepareObjectQuery($query, $relationshipType, $model, $objectOptions);

        return $query;
    }

    /**
     * __method_queryParentRelations_description__
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_queryParentRelations_type__ __return_queryParentRelations_description__
     */
    public function queryParentRelations($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelations('parents', $model, $relationOptions, $objectOptions);
    }

    /**
     * __method_queryChildRelations_description__
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @return __return_queryChildRelations_type__ __return_queryChildRelations_description__
     */
    public function queryChildRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations('children', $model, $relationOptions);
    }

    /**
     * __method_queryAllRelations_description__
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @return __return_queryAllRelations_type__ __return_queryAllRelations_description__
     */
    public function queryAllRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations(false, $model, $relationOptions);
    }

    /**
     * __method_queryRelations_description__
     * @param __param_relationshipType_type__ $relationshipType __param_relationshipType_description__
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @return __return_queryRelations_type__ __return_queryRelations_description__
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
     * __method_siblingObjectQuery_description__
     * @param __param_parent_type__ $parent __param_parent_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_siblingObjectQuery_type__ __return_siblingObjectQuery_description__
     */
    public function siblingObjectQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildObjects($objectClass, $relationOptions, $objectOptions);
    }

    /**
     * __method_siblingRelationQuery_description__
     * @param __param_parent_type__ $parent __param_parent_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_siblingRelationQuery_type__ __return_siblingRelationQuery_description__
     */
    public function siblingRelationQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildRelations($objectClass, $relationOptions, $objectOptions);
    }

    /**
     * __method__prepareSiblingOptions_description__
     * @param __param_relationOptions_type__ $relationOptions __param_relationOptions_description__
     */
    protected function _prepareSiblingOptions(&$relationOptions)
    {
        if (!isset($relationOptions['where'])) {
            $relationOptions['where'] = [];
        } else {
            $relationOptions['where'] = ['and', $relationOptions['where']];
        }
        if (!isset($relationOptions['params'])) { $relationOptions['params'] = []; }
        $objectClass = get_class($this->owner);
        $relationOptions['where'][] =  ['and', '%alias%.'. $this->parentObjectField .' != :ownerPrimaryKey'];
        $relationOptions['params'][':ownerPrimaryKey'] = $this->owner->primaryKey;
    }

    /**
     * __method_hasParent_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param __param_check_type__ $check __param_check_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return_hasParent_type__ __return_hasParent_description__
     */
    public function hasParent($model, $check = null, $relationOptions = [], $objectOptions = [])
    {
        return $this->hasAncestor($model, $check, $relationOptions, $objectOptions, 1);
    }

    /**
     * __method_hasAncestor_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param __param_check_type__ $check __param_check_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @param __param_maxLevels_type__ $maxLevels __param_maxLevels_description__ [optional]
     * @return __return_hasAncestor_type__ __return_hasAncestor_description__
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
     * __method_ancestors_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @param __param_maxLevels_type__ $maxLevels __param_maxLevels_description__ [optional]
     * @param integer $currentLevel __param_currentLevel_description__ [optional]
     * @return __return_ancestors_type__ __return_ancestors_description__
     */
    public function ancestors($model, $relationOptions = [], $objectOptions = [], $maxLevels = null, $currentLevel = 0)
    {
        $currentLevel++;
        $ancestors = $this->queryParentObjects($model, $relationOptions, $objectOptions)->all();
        if (!is_null($maxLevels) && $currentLevel >= $maxLevels) { return $ancestors; }
        foreach ($ancestors as $a) {
            $superAncestors = $a->ancestors($model, $relationOptions, $objectOptions, $maxLevels, $currentLevel);
            foreach ($superAncestors as $key => $aa) {
                $ancestors[] = $aa;
            }
        }

        return $ancestors;
    }

    /**
     * __method_descendants_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @param __param_maxLevels_type__ $maxLevels __param_maxLevels_description__ [optional]
     * @param integer $currentLevel __param_currentLevel_description__ [optional]
     * @return __return_descendants_type__ __return_descendants_description__
     */
    public function descendants($model, $relationOptions = [], $objectOptions = [], $maxLevels = null, $currentLevel = 0)
    {
        $currentLevel++;
        $descendants = $this->owner->queryChildObjects($model, $relationOptions, $objectOptions)->all();
        if (!is_null($maxLevels) && $currentLevel >= $maxLevels) { return $descendants; }
        foreach ($descendants as $a) {
            $superDescendants = $a->descendants($model, $relationOptions, $objectOptions, $maxLevels, $currentLevel);
            foreach ($superDescendants as $key => $aa) {
                $descendants[] = $aa;
            }
        }

        return $descendants;
    }

    /**
     * __method__aliasKeys_description__
     * @param __param_conditions_type__ $conditions __param_conditions_description__
     * @param __param_alias_type__ $alias __param_alias_description__
     * @return __return__aliasKeys_type__ __return__aliasKeys_description__
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
     * __method__prepareObjectQuery_description__
     * @param yii\db\Query $query __param_query_description__
     * @param boolean $relationshipType __param_relationshipType_description__ [optional]
     * @param boolean $model __param_model_description__ [optional]
     * @param array $objectOptions __param_objectOptions_description__ [optional]
     * @return __return__prepareObjectQuery_type__ __return__prepareObjectQuery_description__
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
     * __method__prepareRegistryModelCheck_description__
     * @param __param_query_type__ $query __param_query_description__
     * @param __param_relationshipType_type__ $relationshipType __param_relationshipType_description__
     * @param __param_model_type__ $model __param_model_description__
     */
    protected function _prepareRegistryModelCheck($query, $relationshipType, $model)
    {
        if ($model) {
            $relationClass = Yii::$app->classes['Relation'];
            $registryClass = Yii::$app->classes['Registry'];
            $registryTableAlias = $registryClass::tableName() . ' ' . $this->registryAlias;
            if ($relationshipType === 'children') {
                $relationKey = $this->relationAlias . '.'. $this->childObjectField;
            } else {
                $relationKey = $this->relationAlias . '.'. $this->parentObjectField;
            }
            if (!is_object($model)) {
                $model = new $model;
            }
            $query->leftJoin($registryTableAlias, $this->registryAlias . '.'. $registryClass::primaryKey()[0] .'='.  $relationKey);
            $query->andWhere([$this->registryAlias .'.'. $this->registryModelField => $model::modelAlias()]);
        }
    }

    /**
     * __method__prepareRelationQuery_description__
     * @param yii\db\Query $query __param_query_description__
     * @param boolean $relationshipType __param_relationshipType_description__ [optional]
     * @param boolean $model __param_model_description__ [optional]
     * @param array $relationOptions __param_relationOptions_description__ [optional]
     * @return __return__prepareRelationQuery_type__ __return__prepareRelationQuery_description__
     */
    protected function _prepareRelationQuery(Query $query, $relationshipType = false, $model = false, $relationOptions = [])
    {
        $activeOnly = !isset($relationOptions['activeOnly']) || $relationOptions['activeOnly'];
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
                $model = new $modelClass;
            }
            $modelPrimaryKey = $modelClass::primaryKey()[0];
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
                    ['{{'. $relationAlias .'}}.[['. $this->parentObjectField .']]' => $this->owner->primaryKey],
                    ['{{'. $relationAlias .'}}.[['. $this->childObjectField .']]' => $this->owner->primaryKey]
                ];
            }

            if (!$relationQuery && isset($primaryKey)) {
                $conditions[] = '{{'. $relationAlias .'}}.[['. $primaryKey .']] = {{'. $this->objectAlias .'}}.[['. $modelPrimaryKey .']]';
            }
        }

        if (isset($foreignKey)) {
            $query->andWhere(['{{'. $relationAlias .'}}.[['. $foreignKey .']]' => $this->owner->primaryKey]);
        }

        if ($activeOnly) {
            $isActiveCondition = [$relationAlias .'.'.$this->activeField => 1];
            if (isset($activeConditions[$this->activeField])) {
                $isActiveCondition = $activeConditions[$this->activeField];
                unset($activeConditions[$this->activeField]);
            }
            $startDateCondition = ['or', '{{'. $relationAlias .'}}.[['. $this->startDateField . ']] IS NULL', '{{'. $relationAlias .'}}.[['. $this->startDateField .']] <= CURDATE()'];
            if (isset($activeConditions[$this->startDateField])) {
                $startDateCondition = $activeConditions[$this->startDateField];
                unset($activeConditions[$this->startDateField]);
            }
            $endDateCondition = ['or', '{{'. $relationAlias .'}}.[['. $this->endDateField . ']] IS NULL', '{{'. $relationAlias .'}}.[['. $this->endDateField .']] >= CURDATE()'];
            if (isset($activeConditions[$this->endDateField])) {
                $endDateCondition = $activeConditions[$this->endDateField];
                unset($activeConditions[$this->endDateField]);
            }
            $parts = ['isActive', 'endDate', 'startDate'];
            foreach ($parts as $part) {
                $var = $part .'Condition';
                if (isset($$var) && $$var) {
                    $conditions[] = $this->_aliasKeys($$var, $relationAlias);
                }
            }
        }

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
        $this->_applyOptions($query, $relationOptions);

        return $query;
    }

    /**
     * __method_addActiveConditions_description__
     * @param __param_query_type__ $query __param_query_description__
     * @param __param_alias_type__ $alias __param_alias_description__ [optional]
     */
    public function addActiveConditions($query, $alias = null)
    {
        if (is_null($alias)) {
            $alias = $this->relationAlias;
        }
        if ($alias === false) {
            $alias = '';
        } else {
            $alias = '{{'.$alias .'}}.';
        }
        $conditions = ['and'];
        $conditions[] = [$alias .'[['. $this->activeField .']]' => 1];
        $conditions[] = ['or', $alias .'[['. $this->startDateField . ']] IS NULL', $alias .'[['. $this->startDateField .']] <= CURDATE()'];
        $conditions[] = ['or', $alias .'[['. $this->endDateField . ']] IS NULL', $alias .'[['. $this->endDateField .']] >= CURDATE()'];
        $query->andWhere($conditions);
    }

    /**
     * __method__applyOptions_description__
     * @param yii\db\Query $query __param_query_description__
     * @param array $options __param_options_description__ [optional]
     * @return __return__applyOptions_type__ __return__applyOptions_description__
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
     * __method_isParentPrimary_description__
     * @param __param_companionId_type__ $companionId __param_companionId_description__
     * @return __return_isParentPrimary_type__ __return_isParentPrimary_description__
     */
    public function isParentPrimary($companionId)
    {
        $key = 'parent-'. $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getRelation($companionId, $this->owner->primaryKey);
            if ($relation) {
                return !empty($relation->primary);
            }
        }

        return false;
    }

    /**
     * __method_isChildPrimary_description__
     * @param __param_companionId_type__ $companionId __param_companionId_description__
     * @return __return_isChildPrimary_type__ __return_isChildPrimary_description__
     */
    public function isChildPrimary($companionId)
    {
        $key = 'child-'. $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getRelation($this->owner->primaryKey, $companionId);
            if ($relation) {
                return !empty($relation->primary);
            }
        }

        return false;
    }

    /**
     * __method_parentModel_description__
     * @param __param_companionId_type__ $companionId __param_companionId_description__
     * @return __return_parentModel_type__ __return_parentModel_description__
     */
    public function parentModel($companionId)
    {
        $key = 'parent-'. $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['model'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getRelation($companionId, $this->owner->primaryKey);
            if ($relation && ($parent = $relation->parentObject)) {
                return get_class($parent);
            }
        }

        return false;
    }

    /**
     * __method_childModel_description__
     * @param __param_companionId_type__ $companionId __param_companionId_description__
     * @return __return_childModel_type__ __return_childModel_description__
     */
    public function childModel($companionId)
    {
        $key = 'child-'. $companionId;
        if (isset($this->_relations[$key]) && isset($this->_relations[$key]['primary'])) {
            return $this->_relations[$key]['primary'];
        } else {
            $relationClass = Yii::$app->classes['Relation'];
            $relation = $this->getRelation($this->owner->primaryKey, $companionId);
            if ($relation && ($child = $relation->childObject)) {
                return get_class($child);
            }
        }

        return false;
    }

    /**
     * Get relation
     * @param __param_parentObject_type__ $parentObject __param_parentObject_description__
     * @param __param_childObject_type__ $childObject __param_childObject_description__
     * @return __return_getRelation_type__ __return_getRelation_description__
     */
    public function getRelation($parentObject, $childObject)
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

}
