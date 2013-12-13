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

	public $defaultRelation = [
		'active' => 1
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

    public function queryParentObjects($model, $relationOptions = [], $objectOptions = [])
    {
    	return $this->queryRelativeObjects('parents', $model, $relationOptions, $objectOptions);
    }

    public function queryChildObjects($model, $relationOptions = [], $objectOptions = [])
    {
    	return $this->queryRelativeObjects('children', $model, $relationOptions, $objectOptions);
    }


    public function queryParentRelations($model = false, $relationOptions = [])
    {
    	return $this->queryRelations('parents', $model, $relationOptions, $objectOptions);
    }


    public function queryChildRelations($model = false, $relationOptions = [])
    {
    	return $this->queryRelations('children', $model, $relationOptions);
    }

    protected function _prepareRelationQuery(Query $query, $relationOptions = [])
    {

    }

    protected function _prepareObjectQuery(Query $query, $objectOptions = [])
    {

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
		$_relationModel = $this->relationClass;
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
