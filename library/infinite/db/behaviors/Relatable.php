<?php
/**
 * library/db/behaviors/Relatable.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use yii\db\Query;
use infinite\db\Tree;
use infinite\helpers\ArrayHelper;

class Relatable extends \infinite\db\behaviors\ActiveRecord
{
	public $relationClass = 'app\models\Relation';
	public $registryClass = 'app\models\Registry';

	public $parentObjectField = 'parent_object_id';
	public $childObjectField = 'child_object_id';
	public $activeField = 'active';
	public $startDateField = 'start';
	public $endDateField = 'end';

	public $registryModelField = 'model';

	public $objectAlias = 'o';
	public $relationAlias = 'r';
	public $registryAlias = 'x';


	public $defaultRelation = [
		$this->activeField => 1
	];

	public $child_object_id;
	public $parent_object_id;
	static $_tree_segments = [];

	protected static $_relationModels = [];
	protected static $_relationModelsOld = [];
	protected $_relationsKey;

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

    public function afterSave($event) {
    	$relationModelKey = $this->relationsKey;
    	if (!empty($this->owner->primaryKey) && !empty(self::$_relationModels[$relationModelKey])) {
    		if (!isset(self::$_relationModelsOld[$relationModelKey])) {
    			self::$_relationModelsOld[$relationModelKey] = [];
    		}
    		foreach (self::$_relationModels[$relationModelKey] as $key => $model) {
    			unset(self::$_relationModelsOld[$relationModelKey][$key]);
    			if (!is_object($model)) { continue; }
    			if (empty($model->parent_object_id) && empty($model->child_object_id)) {
    				continue;
    			}
    			if (empty($model->parent_object_id)) {
    				$model->parent_object_id = $this->owner->primaryKey;
    			} elseif (empty($model->child_object_id)) {
    				$model->child_object_id = $this->owner->primaryKey;
    			}
    			if ($model->isNewRecord) {
    				$dirty = $model->getDirtyAttributes(array_keys($this->defaultRelation));
    				foreach ($this->defaultRelation as $dkey => $dvalue) {
    					if (!isset($dirty[$dkey])) {
    						$model->{$dkey} = $dvalue;
    					}
    				}
    			}
    			if (!$model->save()) {
    				$event->handled = false;
    			}
    		}
    		foreach (self::$_relationModelsOld[$relationModelKey] as $relationId) {
    			$relationClass = $this->relationClass;
    			$relation = $relationClass::getOne($relationId);
    			if ($relation && !$relation->delete()) {
    				$event->handled = false;
    			}
    		}
    		self::$_relationModels[$relationModelKey] = self::$_relationModelsOld[$relationModelKey] = null;
    	}
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

    public function getRelationModels($activeOnly = false) {
    	$relationModelKey = md5(serialize(['key' => $this->relationsKey, 'activeOnly' => $activeOnly]));;
    	if (!isset(self::$_relationModels[$relationModelKey])) {
    		if ($this->owner->isNewRecord) {
    			self::$_relationModels[$relationModelKey] = self::$_relationModelsOld[$relationModelKey] = [];
    		} else {
    			self::$_relationModels[$relationModelKey] = self::$_relationModelsOld[$relationModelKey] = $this->queryAllRelations(false, ['activeOnly' => $activeOnly]);
    		}
    	}
    	return self::$_relationModels[$relationModelKey];
    }

    public function registerRelationModel($model) {
    	$relationModelKey = $this->relationsKey;
    	$id = $model->tabularId;
    	if (!isset($_relationModels[$relationModelKey])) { $_relationModels[$relationModelKey] = []; }
		$idParts = explode(':', $id);

		// if we're working with an existing relation in the database, pull it
		if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->relationModels[$idParts[3]])) {
			$id = $idParts[3];
		}
		self::$_relationModels[$relationModelKey][$id] = $model;
    }

	public function getRelationModel($id) {
    	$relationModelKey = $this->relationsKey;
    	if (!isset($_relationModels[$relationModelKey])) { $_relationModels[$relationModelKey] = []; }

		$idParts = explode(':', $id);

		// if we're working with an existing relation in the database, pull it
		if (isset($idParts[3]) && substr($idParts[3], 0, 1) !== '_' && isset($this->relationModels[$idParts[3]])) {
			$id = $idParts[3];
		}
		// for lazy loading relations
		if (isset($this->relationModels[$id]) && !is_object($this->relationModels[$id])) {
			$relationClass = $this->relationClass;
			self::$_relationModels[$relationModelKey][$id] = $relationClass::getOne($this->relationModels[$id]);
		}

		if (empty(self::$_relationModels[$relationModelKey][$id])) {
			self::$_relationModels[$relationModelKey][$id] = new $this->relationClass;
			self::$_relationModels[$relationModelKey][$id]->tabularId = $id;
		}
		return $this->relationModels[$id];
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

		$query = $modelClass::createQuery();
		$this->_prepareRelationQuery($query, $relationshipType, $model, $relationOptions);
		$this->_prepareObjectQuery($query, $relationshipType, $model, $objectOptions);
    	return $query;
    }

    public function queryParentRelations($model = false, $relationOptions = [])
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
    	$relationClass = $this->relationClass;
    	$query = $relationClass::createQuery();
    	$this->_prepareRelationQuery($query, $relationshipType, $model, $relationOptions);
    	$this->_prepareRegistryModelCheck($query, $model);
    	return $query;
    }

    public function siblingObjectQuery($parent, $relationOptions = [], $objectOptions = [])
    {
    	$this->_prepareSiblingOptions($relationOptions);
		return $parent->queryChildObjects($objectClass, $relationOptions, $objectOptions);
    }


    public function siblingRelationQuery($parent, $relationOptions = [], $objectOptions = [])
    {
    	$this->_prepareSiblingOptions($relationOptions);
		return $parent->queryChildRelations($objectClass, $relationOptions, $objectOptions);
    }

    protected function _prepareSiblingOptions(&$relationOptions)
    {
    	if (!isset($relationOptions['where'])) { $relationOptions['where'] = []; }
    	if (!isset($relationOptions['params'])) { $relationOptions['params'] = []; }
    	$relationOptions['where'][] = $this->objectAlias .'.'. $objectClass::primaryKey() .' != :ownerPrimaryKey';
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

	protected function _prepareObjectQuery(Query $query, $relationshipType = false, $model = false, $objectOptions = [])
    {
    	$relationClass = $this->relationClass;
    	$relationTableAlias = $relationClass::tableName() . ' ' . $this->relationAlias;
    	if (!empty($objectOptions['where'])) {
    		$query->andWhere($objectOptions['where']);
    		unset($objectOptions['where']);
    	}
    	if (!empty($objectOptions['params'])) {
    		$query->addParams($objectOptions['params']);
    		unset($objectOptions['params']);
    	}
    	$this->_applyOptions($query, $objectOptions);
    	return $query;
    }

    protected function _prepareRegistryModelCheck($query, $model)
    {
    	if ($model) {
    		$relationClass = $this->relationClass;
    		$registryClass = $this->registryClass;
    		$registryTableAlias = $registryClass::tableName() . ' ' . $this->registryAlias;
    		$query->leftJoin($registryTableAlias, [$this->registryAlias . '.'. $registryClass::primaryKey() => $this->relationClass . '.'. $relationClass::primaryKey()]);
    		$query->addWhere([$this->registryAlias .'.'. $this->registryModelField => $model]);
    	}
    }

    protected function _prepareRelationQuery(Query $query, $relationshipType = false, $model = false, $relationOptions = [])
    {
    	$activeOnly = !isset($relationOptions['activeOnly']) || $relationOptions['activeOnly'];
    	$relationClass = $this->relationClass;
    	$relationTableAlias = $relationClass::tableName() . ' ' . $this->relationAlias;
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
    	}

    	$relationQuery = $query->modelClass === $this->relationClass || !$model;

    	if ($relationQuery) {
    		$conditionsDestination = 'where';
    	} else {
    		$conditionsDestination = 'on';
    	}

    	if ($model && $relationshipType === 'parents') {
			$primaryKey = $this->parentObjectField;
			$foreignKey = $this->childObjectField;
    	} elseif ($model && $relationshipType === 'children') { {
			$primaryKey = $this->parentObjectField;
			$foreignKey = $this->childObjectField;
    	} else {
    		$conditions[] = [
    			'or',
    			[$this->relationAlias .'.'. $this->parentObjectField => $this->owner->primaryKey], 
    			[$this->relationAlias .'.'. $this->childObjectField => $this->owner->primaryKey]
    		];
    	}

    	if ($model && isset($primaryKey)) {
			$conditions[] = [$this->relationAlias .'.'. $primaryKey => $this->objectAlias .'.'. $modelClass::primaryKey()];
		}

    	if ($model && isset($foreignKey)) {
			$query->andWhere([$this->relationAlias .'.'. $foreignKey => $this->owner->primaryKey]);
		}

		if ($activeOnly) {
			$isActiveCondition = [$this->activeField => 1];
			if (isset($activeConditions[$this->activeField])) {
				$isActiveCondition = $activeConditions[$this->activeField];
				unset($activeConditions[$this->activeField]);
			}
			$startDateCondition = ['or', $this->relationAlias .'.'. $this->startDateField . ' IS NULL', $this->relationAlias .'.'. $this->startDateField .' < NOW()'];
			if (isset($activeConditions[$this->startDateField])) {
				$startDateCondition = $activeConditions[$this->startDateField];
				unset($activeConditions[$this->startDateField]);
			}
			$endDateCondition = ['or', $this->relationAlias .'.'. $this->endDateField . ' IS NULL', $this->relationAlias .'.'. $this->endDateField .' > NOW()'];
			if (isset($activeConditions[$this->endDateField])) {
				$endDateCondition = $activeConditions[$this->endDateField];
				unset($activeConditions[$this->endDateField]);
			}
			$parts = ['isActive', 'endDate', 'startDate'];
			foreach ($parts as $part) {
				$var = $part .'Condition';
				if (isset($$var) && $$var) {
					$conditions[] = $$var;
				}
			}
		}

		if (!empty($activeConditions)) {
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

    protected function _applyOptions(Query $query, $options = [])
    {
    	foreach ($options as $method => $args) {
    		if (method_exists($query, $method)) {
    			$query->$method($args);
    		}
    	}
    	return true;
    }


	// public function buildTree($levels = 5, $model = null, $relationOptions = [], $modelOptions = []) {
	// 	$object = $this->owner;
	// 	if (is_object($model)) {
	// 		$object = $model;
	// 	}
	// 	$model = get_class($object);
	// 	$key = md5(serialize(['object_model' => $model, 'object' => $object->primaryKey]));
	// 	if (isset(self::$_tree_segments[$key])) {
	// 		return self::$_tree_segments[$key];
	// 	}
	// 	$children = [];
	// 	if ($levels > 0) {
	// 		foreach ($object->children($model, $relationAttr, $modelOptions) as $child) {
	// 			$children[] = $child->buildTree($levels - 1, $child, $relationAttr, $modelOptions);
	// 		}
	// 	}
	// 	return self::$_tree_segments[$key] = new Tree($object, $children);
	// }
}
