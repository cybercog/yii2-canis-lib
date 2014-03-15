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
use infinite\base\exceptions\Exception;

class ActiveAccess extends \infinite\db\behaviors\ActiveRecord
{
    const ACCESS_NONE = 0x10;           // null
    const ACCESS_OWNER_ADMIN = 0x11;    // -1
    const ACCESS_ADMIN = 0x12;          // -2
    const ACCESS_SUPER_ADMIN = 0x13;    // -3
    const ACCESS_PARENT = 0x30;         // 0
	const ACCESS_GRANTED = 0x20;        // 1

	// from QueryAccess
	protected $_access;
	protected $_acaId;
	protected $_accessingObject;

	protected $_accessMap = [];

	public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

    public function fillAccessMap($accessingObject = null, $ensureAca = null)
    {
    	$allAcas = array_keys(Yii::$app->gk->actionsById);
        if (!is_null($ensureAca)) {
            $aca = is_object($ensureAca) ? $ensureAca->primaryKey : $ensureAca;
            if (!in_array($aca, $allAcas)) {
                \d("clear");exit;
                Yii::$app->gk->clearActionsCache();
                $allAcas = array_keys(Yii::$app->gk->actionsById);
                \d($allAcas);exit;
            }
        }
    	$currentAcas = array_keys($this->_accessMap);
    	$needAcas = array_diff($allAcas, $currentAcas);
        if (count($allAcas) === count($needAcas)) {
            $needAcas = true;
        }
    	$access = Yii::$app->gk->getAccess($this->owner, $accessingObject, $needAcas);
    	foreach ($access as $key => $value) {
    		$this->_accessMap[$key] = $value;
    	}
        if (isset($aca)) {
            if (!isset($this->_accessMap[$aca])) {
                \d($currentAcas);
                \d($needAcas);
                \d($access);
                \d($aca);
                exit;
            }
        }
    }

    public function can($aca, $accessingObject = null, $trustParent = false)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
    	if (!is_object($aca)) {
    		$aca = Yii::$app->gk->getActionObjectByName($aca);
    	}
    	if (!isset($this->_accessMap[$aca->primaryKey])) {
    		$this->fillAccessMap($accessingObject, $aca);
    	}
    	if (!isset($this->_accessMap[$aca->primaryKey])) {
    		throw new Exception("Access map fill failed! {$aca->primaryKey}" . print_r($this->_accessMap, true));
    	}
    	if ($this->_accessMap[$aca->primaryKey] === self::ACCESS_PARENT) {
            if ($trustParent) {
                $this->_accessMap[$aca->primaryKey] = self::ACCESS_GRANTED;
            } else {
    	       $this->_accessMap[$aca->primaryKey] = $this->parentCan($aca, $accessingObject);
            }
    	}
    	return $this->_accessMap[$aca->primaryKey] === self::ACCESS_GRANTED;
    }

    public function parentCan($aca, $accessingObject = null)
    {
    	//return self::ACCESS_GRANTED; // @todo fix
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
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
		if (isset($this->_access) && isset($this->_acaId) && !empty($this->owner->isAccessControlled)) {
			if (is_array($this->_acaId)) {
				foreach ($this->_acaId as $acaId) {
					$this->_accessMap[$acaId] = self::translateAccessValue($this->_access);
				}
			} else {
				$this->_accessMap[$this->_acaId] = self::translateAccessValue($this->_access);
			}
		}
	}
    
	public function allow($action, $accessingObject = null, $aclRole = null) {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
		return Yii::$app->gk->allow($action, $this->owner, $accessingObject, get_class($this->owner), $aclRole);
	}

	public function clear($action, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
		return Yii::$app->gk->clear($action, $this->owner, $accessingObject, get_class($this->owner), $aclRole);
	}

    public function requireOwnerAdmin($action, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        return Yii::$app->gk->requireOwnerAdmin($action, $this->owner, $accessingObject, get_class($this->owner), $aclRole);
    }
    
    public function requireAdmin($action, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        return Yii::$app->gk->requireAdmin($action, $this->owner, $accessingObject, get_class($this->owner), $aclRole);
    }
    
    public function requireSuperAdmin($action, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        return Yii::$app->gk->requireSuperAdmin($action, $this->owner, $accessingObject, get_class($this->owner), $aclRole);
    }

	public static function translateAccessValue($value)
	{
		if ($value == 0) {
			return self::ACCESS_PARENT;
        } elseif ($value == 1) {
            return self::ACCESS_GRANTED;
        } elseif ($value == -1) {
            return self::ACCESS_OWNER_ADMIN;
        } elseif ($value == -2) {
            return self::ACCESS_ADMIN;
        } elseif ($value == -3) {
            return self::ACCESS_SUPER_ADMIN;
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


    public function asUser($userName)
    {
        $user = null;
        if (($testUser = Yii::$app->gk->getUser($userName)) && !empty($testUser)) {
            $user = $testUser;
        }
        return $this->asInternal($user);
    }

    public function asGroup($groupSystemName)
    {
        $group = null;
        if (($testGroup = Yii::$app->gk->getGroup($groupSystemName)) && !empty($testGroup)) {
            $group = $testGroup;
        }
        return $this->asInternal($group);
    }

    public function asInternal($acr)
    {
        $this->accessingObject = $acr;
        return $this->owner;
    }

    public function setAccessingObject($value)
    {
        return $this->_accessingObject = $value;
    }


    public function getAccessingObject()
    {
        return $this->_accessingObject;
    }
}
