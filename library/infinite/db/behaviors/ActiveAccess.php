<?php
/**
 * library/db/behaviors/ActiveAccess.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;
use yii\db\Query;

class ActiveAccess extends \infinite\db\behaviors\ActiveRecord
{
	const ACCESS_NONE = 0x10;
	const ACCESS_GRANTED = 0x20;
	const ACCESS_PARENT = 0x30;

	// from QueryAccess
	protected $_access;
	protected $_acaId;

	protected $_accessMap = [];

	public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
        ];
    }

    public function fillAccessMap($accessingObject = null)
    {
    	$allAcas = array_keys(Yii::$app->gk->actionsById);
    	$currentAcas = array_keys($this->_accessMap);
    	$needAcas = array_diff($allAcas, $currentAcas);
    	$access = Yii::$app->gk->getAccess($this->owner, $accessingObject, $needAcas);
    	foreach ($access as $key => $value) {
    		$this->_accessMap[$key] = $value;
    	}
    }

    public function can($aca, $accessingObject = null)
    {
    	if (!is_object($aca)) {
    		$aca = Yii::$app->gk->getActionObjectByName($aca);
    	}
    	if (!isset($this->_accessMap[$aca->primaryKey])) {
    		$this->fillAccessMap($accessingObject);
    	}
    	if (!isset($this->_accessMap[$aca->primaryKey])) {
    		throw new Exception("Access map fill failed!");
    	}
    	if ($this->_accessMap[$aca->primaryKey] === self::ACCESS_PARENT) {
    		$this->_accessMap[$aca->primaryKey] = $this->parentCan($aca, $accessingObject);
    	}
    	return $this->_accessMap[$aca->primaryKey] === self::ACCESS_GRANTED;
    }

    public function parentCan($aca, $accessingObject = null)
    {
    	return true;
    	if (!is_object($aca)) {
    		$aca = Yii::$app->gk->getActionObjectByName($aca);
    	}
    	$parentIds = $this->owner->queryParentRelations(false)->select(['parent_object_id'])->column();
    	if (Yii::$app->gk->can($aca, $parentIds, $accessingObject)) {
			return self::ACCESS_GRANTED;
		}
    	return self::ACCESS_NONE;
    }

	public function afterFind($event)
	{
		if (isset($this->_access) && isset($this->_acaId)) {
			if (is_array($this->_acaId)) {
				foreach ($this->_acaId as $acaId) {
					$this->_accessMap[$acaId] = self::translateAccessValue($this->_access);
				}
			} else {
				$this->_accessMap[$this->_acaId] = self::translateAccessValue($this->_access);
			}
		}
	}

	public static function translateAccessValue($value)
	{
		if ($value === 0 || $value === '0') {
			return self::ACCESS_PARENT;
		} elseif ($value === 1 || $value === '1') {
			return self::ACCESS_GRANTED;
		} else {
			return self::ACCESS_NONE;
		}
	}

	public function setAccess($value)
	{
		$this->_access = $value;
	}

	public function setAca_id($value)
	{
		if (is_null($value)) {
			$this->_acaId = array_keys(Yii::$app->gk->actionsById);
		} else {
			$this->_acaId = $value;
		}
	}

}
