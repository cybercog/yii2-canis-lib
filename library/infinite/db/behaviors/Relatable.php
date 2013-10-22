<?php
/**
 * library/db/behaviors/Relatable.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use \yii\db\Query;
use \infinite\db\Tree;
use \infinite\helpers\ArrayHelper;

class Relatable extends \infinite\db\behaviors\ActiveRecord
{
	public $child_object_id;
	public $parent_object_id;
	static $_tree_segments = [];
	const RELATION_MODEL = '\app\models\Relation';


	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @return unknown
	 */
	function children($model, $relationOptions = [], $modelOptions = []) {
		return $this->relatives('children', $model, $relationOptions, $modelOptions);
	}


	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @return unknown
	 */
	function parent($model, $relationOptions = [], $modelOptions = []) {
		return $this->relatives('parent', $model, $relationOptions, $modelOptions);
	}


	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @return unknown
	 */
	function parents($model, $relationOptions = [], $modelOptions = []) {
		return $this->relatives('parents', $model, $relationOptions, $modelOptions);
	}

	public function getParentIds($models = null, $relationOptions = []) {
		return $this->relativeIds('parents', $models, $relationOptions);
	}

	public function getChildIds($models = null, $relationOptions = []) {
		return $this->relativeIds('children', $models, $relationOptions);
	}

	public function relativeIds($type, $models = null, $relationOptions = []) {
		$_relationModel = self::RELATION_MODEL;
		$_relation = new $_relationModel;
		$_relationTable = $_relation->tableName();
		$_relationFields = array_keys($_relationModel::::getTableSchema()->columns);
		

		$_registryModel = \infinite\db\behaviors\Registry::REGISTRY_MODEL;
		$_registry = new $_registryModel;
		$_registryTable = $_registry->tableName();

		if (!isset($relationOptions['fields'])) {
			$relationAttr = [];
		} else {
			$relationAttr = $relationOptions['fields'];
		}

		if (in_array('active', $_relationFields)) {
			if (!array_key_exists('active', $relationAttr)) {
				$relationAttr['active'] = 1;
			}
		}
		if (in_array('start', $_relationFields)) {
			if (!array_key_exists('start', $relationAttr)) {
				$relationAttr['start'] = ['IS NULL', '< NOW()'];
			}
		}
		if (in_array('end', $_relationFields)) {
			if (!array_key_exists('end', $relationAttr)) {
				$relationAttr['end'] = ['IS NULL', '> NOW()'];
			}
		}

		if ($type === 'parents') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
		} elseif ($type === 'parent') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
			$modelOptions['limit'] = 1;
		} else {
			$primaryKey = 'child_object_id';
			$foreignKey = 'parent_object_id';
		}

		$whereParts = ['AND'];
		$relationAttr[$foreignKey] = $this->owner->quote($this->owner->id);
		foreach ($relationAttr as $key => $tests) {
			if (empty($relationOn)) {
				$partOn = '(';
			} else {
				$partOn = ' AND (';
			}
			if (is_array($tests)) {
				$key = $_relationModel.'.'.$key;
				$partOn2 = '';
				foreach ($tests as $t) {
					if (!empty($partOn2)) {
						$partOn2 .= ' OR ';
					}
					$partOn2 .= $key;
					if (in_array(substr($t, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
						$partOn2 .= ' '. $t;
					} else {
						$partOn2 .= ' = '. $t;
					}
				}
				$partOn .= $partOn2;
			} else {
				$key = '`'.$_relationModel.'`.`'.$key.'`';
				$partOn .= $key;
				if (in_array(substr($tests, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
					$partOn .= ' '. $tests;
				} else {
					$partOn .= ' = '. $tests;
				}
			}
			$partOn .= ')';
			$whereParts[] = $partOn;
		}
		$where = ['AND', 'Registry.id=:object_id'];
		$relationOn = 'Registry.id = Relation.'. $primaryKey;
		if (!is_null($models)) {
			if (!is_array($models)) {
				$models = [$models];
			}
			$models = $this->owner->quote($models);
			$relationOn .= ' AND Registry.model IN ('.implode(',', $models).')';
		}
		// first build the join
		$selectQuery = new Query;
		$selectQuery->select('Relation.'. $primaryKey);
		$selectQuery->from($_relationTable .' '. $_relationModel);
		$selectQuery->innerJoin($_registryTable .' '. $_registryModel, $relationOn);
		$selectQuery->where($whereParts);
		$command = $selectQuery->createCommand();
		$results = $command->queryColumn();
		return $results;
	}

	/**
	 *
	 *
	 * @param unknown $activeQuery
	 * @param unknown $ownerId
	 * @param unknown $relationOptions (optional)
	 * @return unknown
	 */
	public function searchChildren($activeQuery, $ownerId, $relationOptions = []) {
		return $this->searchRelatives($activeQuery, 'children', $ownerId, $relationOptions);
	}


	/**
	 *
	 *
	 * @param unknown $activeQuery
	 * @param unknown $ownerId
	 * @param unknown $relationOptions (optional)
	 * @return unknown
	 */
	public function searchParents($activeQuery, $ownerId, $relationOptions = []) {
		return $this->searchRelatives($activeQuery, 'parents', $ownerId, $relationOptions);
	}


	/**
	 *
	 *
	 * @param unknown $activeQuery
	 * @param unknown $type
	 * @param unknown $ownerId
	 * @param unknown $relationOptions (optional)
	 * @return unknown
	 */
	public function searchRelatives($activeQuery, $type, $ownerId, $relationOptions = []) {
		$_relationModel = self::RELATION_MODEL;
		$_relation = new $_relationModel;
		$_relationFields = array_keys($_relation->getMetaData()->columns);

		if (!isset($relationOptions['fields'])) {
			$relationAttr = [];
		} else {
			$relationAttr = $relationOptions['fields'];
		}

			if (in_array('active', $_relationFields)) {
			if (!array_key_exists('active', $relationAttr)) {
				$relationAttr['active'] = 1;
			}
		}
		if (in_array('start', $_relationFields)) {
			if (!array_key_exists('start', $relationAttr)) {
				$relationAttr['start'] = ['IS NULL', '< NOW()'];
			}
		}
		if (in_array('end', $_relationFields)) {
			if (!array_key_exists('end', $relationAttr)) {
				$relationAttr['end'] = ['IS NULL', '> NOW()'];
			}
		}

		if ($type === 'parents') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
		}
		elseif ($type === 'parent') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
			$modelOptions['limit'] = 1;
		}
		else {
			$primaryKey = 'child_object_id';
			$foreignKey = 'parent_object_id';
		}

		// first build the join
		$_relationTable = $_relation->tableName();
		// $this->getDbCriteria()->params[':object_id'] = $this->owner->id;
		$relationOn = '';
		$relationAttr[$primaryKey] = 't.id';
		foreach ($relationAttr as $key => $tests) {
			if (empty($relationOn)) {
				$partOn = '(';
			} else {
				$partOn = ' AND (';
			}
			if (is_array($tests)) {
				$key = $_relationModel.'.'.$key;
				$partOn2 = '';
				foreach ($tests as $t) {
					if (!empty($partOn2)) {
						$partOn2 .= ' OR ';
					}
					$partOn2 .= $key;
					if (in_array(substr($t, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
						$partOn2 .= ' '. $t;
					} else {
						$partOn2 .= ' = '. $t;
					}
				}
				$partOn .= $partOn2;
			} else {
				$key = '`'.$_relationModel.'`.`'.$key.'`';
				$partOn .= $key;
				if (in_array(substr($tests, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
					$partOn .= ' '. $tests;
				} else {
					$partOn .= ' = '. $tests;
				}
			}
			$partOn .= ')';
			$relationOn .= $partOn;
		}
		$activeQuery->join('LEFT JOIN', $_relationTable.' AS '.$_relationModel, '('.$relationOn.')');
		// then connect the join to the model
		if (isset($relationOptions['params'])) {
			$activeQuery->params = $relationOptions['params'];
		}
		$activeQuery->where([$_relationModel .'.'.$foreignKey => $ownerId]);

		return $activeQuery;
	}


	/**
	 *
	 *
	 * @param unknown $type
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @return unknown
	 */
	function relatives($type, $model, $relationOptions = [], $modelOptions = []) {
		$_relationModel = self::RELATION_MODEL;
		$_relation = new $_relationModel;
		$_relationFields = array_keys($_relation->getMetaData()->columns);
		
		if (!isset($relationOptions['fields'])) {
			$relationAttr = [];
		} else {
			$relationAttr = $relationOptions['fields'];
		}

		if (in_array('active', $_relationFields)) {
			if (!array_key_exists('active', $relationAttr)) {
				$relationAttr['active'] = 1;
			}
		}
		if (in_array('start', $_relationFields)) {
			if (!array_key_exists('start', $relationAttr)) {
				$relationAttr['start'] = ['IS NULL', '< NOW()'];
			} elseif ($relationAttr['start'] === true) {
				unset($relationAttr['start']);
			}
		}
		if (in_array('end', $_relationFields)) {
			if (!array_key_exists('end', $relationAttr)) {
				$relationAttr['end'] = ['IS NULL', '> NOW()'];
			} elseif ($relationAttr['end'] === true) {
				unset($relationAttr['end']);
			}
		}

		if (!class_exists($model)) {
			throw new Exception("Model {$model} does not exist!");
		}

		if ($type === 'parents') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
		}
		elseif ($type === 'parent') {
			$primaryKey = 'parent_object_id';
			$foreignKey = 'child_object_id';
			$modelOptions['limit'] = 1;
		}
		else {
			$primaryKey = 'child_object_id';
			$foreignKey = 'parent_object_id';
		}
		$o = $model::find();
		// first build the join
		$_relationTable = $_relation->tableName();
		// $this->getDbCriteria()->params[':object_id'] = $this->owner->id;
		$relationOn = '';
		$relationAttr[$primaryKey] = 't.id';
		foreach ($relationAttr as $key => $tests) {
			if (empty($relationOn)) {
				$partOn = '(';
			} else {
				$partOn = ' AND (';
			}
			if (is_array($tests)) {
				$key = $_relationModel.'.'.$key;
				$partOn2 = '';
				foreach ($tests as $t) {
					if (!empty($partOn2)) {
						$partOn2 .= ' OR ';
					}
					$partOn2 .= $key;
					if (in_array(substr($t, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
						$partOn2 .= ' '. $t;
					} else {
						$partOn2 .= ' = '. $t;
					}
				}
				$partOn .= $partOn2;
			} else {
				$key = '`'.$_relationModel.'`.`'.$key.'`';
				$partOn .= $key;
				if (in_array(substr($tests, 0, 2), ['> ', '< ', '>=', '<=', '<>', '!=', 'IS'])) {
					$partOn .= ' '. $tests;
				} else {
					$partOn .= ' = '. $tests;
				}
			}
			$partOn .= ')';
			$relationOn .= $partOn;
		}
		$o->join('LEFT JOIN', $_relationTable.' AS '.$_relationModel, '('.$relationOn.')');
		// then connect the join to the model
		if (isset($relationOptions['params'])) {
			$o->params = array_merge($o->getDbCriteria()->params, $relationOptions['params']);
		}
		$o->where([$_relationModel .'.'.$foreignKey => $this->owner->id]);

		// then add in model attributes (if there are any)
		foreach ($modelOptions as $k => $v) {
			if (in_array($k, ['order', 'orderBy', 'limit', 'fields', 'field', 'notField', 'disableAcl', 'enableAcl'])) {
				$o->$k($v);
			}
		}
		$results = $o->all();
		if ($type === 'parent' AND isset($results[0])) {
			return $results[0];
		}
		return $results;
	}


	public function siblings($parent, $relationOptions = [], $modelOptions = []) {

		if (!isset($modelOptions['where'])) { $modelOptions['where'] = []; }
		if (!isset($modelOptions['params'])) { $modelOptions['params'] = []; }
		$modelOptions['where'] = array_merge(['id != :ownerPrimaryKey'], $modelOptions['where']);
		$modelOptions['params'][':ownerPrimaryKey'] = $this->owner->primaryKey;

		return $parent->children(get_class($this->owner), $relationOptions, $modelOptions);
	}

	public function siblingList($parent, $relationOptions = [], $modelOptions = []) {
		return ArrayHelper::map($this->siblings($parent, $relationOptions, $modelOptions), 'id', 'descriptor');
	}

	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $check           (optional)
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @return unknown
	 */
	public function hasParent($model, $check = null, $relationOptions = [], $modelOptions = []) {
		return $this->owner->hasAncestor($model, $check, $relationOptions, $modelOptions, 1);
	}


	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $check           (optional)
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @param unknown $maxLevels       (optional)
	 * @return unknown
	 */
	public function hasAncestor($model, $check = null, $relationOptions = [], $modelOptions = [], $maxLevels = null) {
		$ancestors = $this->owner->ancestors($model, $relationOptions, $modelOptions, $maxLevels);
		if (is_null($check) and !empty($ancestors)) {
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
	 *
	 *
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @param unknown $maxLevels       (optional)
	 * @param unknown $currentLevel    (optional)
	 * @return unknown
	 */
	public function ancestors($model, $relationOptions = [], $modelOptions = [], $maxLevels = null, $currentLevel = 0) {
		$currentLevel++;
		$ancestors = $this->owner->parents($model, $relationOptions, $modelOptions);
		if (!is_null($maxLevels) and $currentLevel >= $maxLevels) { return $ancestors; }
		foreach ($ancestors as $a) {
			$superAncestors = $a->ancestors($model, $relationOptions, $modelOptions, $maxLevels, $currentLevel);
			foreach ($superAncestors as $key => $aa) {
				$ancestors[] = $aa;
			}
		}
		return $ancestors;
	}

	/**
	 *
	 *
	 * @param unknown $model
	 * @param unknown $relationOptions (optional)
	 * @param unknown $modelOptions    (optional)
	 * @param unknown $maxLevels       (optional)
	 * @param unknown $currentLevel    (optional)
	 * @return unknown
	 */
	public function descendants($model, $relationOptions = [], $modelOptions = [], $maxLevels = null, $currentLevel = 0) {
		$currentLevel++;
		$descendants = $this->owner->children($model, $relationOptions, $modelOptions);
		if (!is_null($maxLevels) and $currentLevel >= $maxLevels) { return $descendants; }
		foreach ($descendants as $a) {
			$superDescendants = $a->descendants($model, $relationOptions, $modelOptions, $maxLevels, $currentLevel);
			foreach ($superDescendants as $key => $aa) {
				$descendants[] = $aa;
			}
		}
		return $descendants;
	}

	/**
	 *
	 *
	 * @param unknown $type         (optional)
	 * @param unknown $activeOnly   (optional)
	 * @param unknown $relationAttr (optional)
	 * @return unknown
	 */
	public function getRelations($type = null, $activeOnly = true, $relationAttr = []) {
		$_relationModel = self::RELATION_MODEL;
		$relations = $_relationModel::find();
		$where = [];
		switch ($type) {
		case 'parents':
			$where['child_object_id'] = $this->owner->id;
			break;
		case 'children':
			$where['parent_object_id'] = $this->owner->id;
			break;
		default:
			$where['or'] = ['parent_object_id' => $this->owner->id, 'child_object_id' => $this->owner->id];
			break;
		}
		if ($activeOnly) {
			if (!array_key_exists('active', $relationAttr)) {
				$where['active'] = 1;
			}
			if (!array_key_exists('start', $relationAttr)) {
				$where[] = ['or' => ['start' => NULL, 'start < NOW()']];
			}
			if (!array_key_exists('end', $relationAttr)) {
				$where[] = ['or' => ['end' => NULL, 'end > NOW()']];
			}
		}
		$relations->where($where);
		return $relations->findAll();
	}


	/**
	 * lazy way of retrieving HAS_ONE related items
	 *
	 * @param unknown $model
	 * @param unknown $modelAttr (optional)
	 * @return unknown
	 */
	function item($model, $modelAttr = []) {
		$item = $this->items($model, $modelAttr, 1);
		if (!isset($item[0])) {
			return false;
		}
		return $item[0];
	}


	public function buildTree($levels = 5, $model = null, $relationOptions = [], $modelOptions = []) {
		$object = $this->owner;
		if (is_object($model)) {
			$object = $model;
		}
		$model = get_class($object);
		$key = md5(serialize(['object_model' => $model, 'object' => $object->primaryKey]));
		if (isset(self::$_tree_segments[$key])) {
			return self::$_tree_segments[$key];
		}
		$children = [];
		if ($levels > 0) {
			foreach ($object->children($model, $relationAttr, $modelOptions) as $child) {
				$children[] = $child->buildTree($levels - 1, $child, $relationAttr, $modelOptions);
			}
		}
		return self::$_tree_segments[$key] = new Tree($object, $children);
	}
}
