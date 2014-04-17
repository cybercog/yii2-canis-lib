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
**/
class Relatable extends \infinite\db\behaviors\ActiveRecord
{
    public $parentObjectField = 'parent_object_id';
    public $childObjectField = 'child_object_id';
    public $activeField = 'active';
    public $startDateField = 'start';
    public $endDateField = 'end';

    public $registryModelField = 'object_model';

    public $objectAlias = 'o';
    public $relationAlias = 'r';
    public $registryAlias = 'x';

    protected static $_relationModels = [];
    protected static $_relationModelsOld = [];
    protected $_relationsKey;
    protected $_relations = [];

    static $_setGlobalEvents = false;

    static $debug = [];

    /*
        Events stuff
    */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function safeAttributes()
    {
        return ['relationModels'];
    }

    public function init()
    {
        parent::init();
        $registryClass = Yii::$app->classes['Registry'];
        if (!self::$_setGlobalEvents) {
            self::$_setGlobalEvents = true;
            Event::on(Yii::$app->classes['Registry'], $registryClass::EVENT_BEFORE_DELETE, [$this, 'cleanupRelations']);
        }
    }

    public function loadAllParentIds()
    {
        return self::getAllParentIds($this->owner);
    }

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

    public function cleanupRelations($event = null)
    {
        return true;
        // @todo implement
        $relatedModels = $event->sender->relationModels;
        foreach ($relatedModels as $model) {
            $model->delete();
        }
    }

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
                    $modelCheck = $relationClass::find()->where(['parent_object_id' => $model->parent_object_id, 'child_object_id' => $model->child_object_id])->one();

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

    public function afterSave($event)
    {
        $this->handleRelationSave($event);
    }

    public function getDefaultRelation()
    {
        return [
            $this->activeField => 1
        ];
    }

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
        self::$_relationModels[$relationModelKey][$id] = ['handled' => false, 'model' => $model];

        return $model;
    }

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

    public function parents($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->all();
    }

    public function parent($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary', SORT_DESC]);

        return $this->queryParentObjects($model, $relationOptions, $objectOptions)->one();
    }

    public function children($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->all();
    }

    public function child($model, $relationOptions = [], $objectOptions = [])
    {
        if (!isset($relationOptions['order'])) {
            $relationOptions['order'] = [];
        }
        array_unshift($relationOptions['order'], ['primary', SORT_DESC]);

        return $this->queryChildObjects($model, $relationOptions, $objectOptions)->one();
    }

    public function getParentIds($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryParentRelations($model, $relationOptions, $objectOptions)->select('parent_object_id')->column();
    }

    public function getChildIds($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryChildRelations($model, $relationOptions, $objectOptions)->select('child_object_id')->column();
    }

    public function queryParentObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('parents', $model, $relationOptions, $objectOptions);
    }

    public function queryChildObjects($model, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelativeObjects('children', $model, $relationOptions, $objectOptions);
    }

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

    public function queryParentRelations($model = false, $relationOptions = [], $objectOptions = [])
    {
        return $this->queryRelations('parents', $model, $relationOptions, $objectOptions);
    }

    public function queryChildRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations('children', $model, $relationOptions);
    }

    public function queryAllRelations($model = false, $relationOptions = [])
    {
        return $this->queryRelations(false, $model, $relationOptions);
    }

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

    public function siblingObjectQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildObjects($objectClass, $relationOptions, $objectOptions);
    }

    public function siblingRelationQuery($parent, $relationOptions = [], $objectOptions = [])
    {
        $objectClass = get_class($this->owner);
        $this->_prepareSiblingOptions($relationOptions);

        return $parent->queryChildRelations($objectClass, $relationOptions, $objectOptions);
    }

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

    public function hasParent($model, $check = null, $relationOptions = [], $objectOptions = [])
    {
        return $this->hasAncestor($model, $check, $relationOptions, $objectOptions, 1);
    }

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

    public function addActiveConditions($query, $alias = null)
    {
        if (is_null($alias)) {
            $alias = $this->relationAlias;
        }
        $conditions = ['and'];
        $conditions[] = [$alias .'.'.$this->activeField => 1];
        $conditions[] = ['or', $alias .'.'. $this->startDateField . ' IS NULL', $alias .'.'. $this->startDateField .' <= CURDATE()'];
        $conditions[] = ['or', $alias .'.'. $this->endDateField . ' IS NULL', $alias .'.'. $this->endDateField .' >= CURDATE()'];
        $query->andWhere($conditions);
    }

    protected function _applyOptions(Query $query, $options = [])
    {
        foreach ($options as $method => $args) {
            if (method_exists($query, $method)) {
                $query->$method($args);
            }
        }

        return true;
    }

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
