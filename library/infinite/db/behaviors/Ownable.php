<?php
/**
 * library/db/behaviors/Registry.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;

class Ownable extends \infinite\db\behaviors\ActiveRecord
{
    public $registryClass = 'app\\models\\Registry';
    public static $_table;
    public $objectOwner;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ];
    }

    public function isEnabled()
    {
        if (empty($this->owner->getBehavior('Registry'))) {
            return false;
        }
        return true;
    }

    public function beforeSave($event)
    {
        
    }
}
