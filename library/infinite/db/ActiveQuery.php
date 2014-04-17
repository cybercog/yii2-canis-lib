<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

use infinite\base\ComponentTrait;

/**
 * ActiveQuery [@doctodo write class description for ActiveQuery]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ActiveQuery extends \yii\db\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
    /**
     * @var __var__model_type__ __var__model_description__
     */
    protected $_model;
    /**
     * Get is aco
     * __method_getIsAco_description__
     *
     * @return __return_getIsAco_type__ __return_getIsAco_description__
     */
    public function getIsAco()
    {
        $class = $this->modelClass;

        return $class::$isAco;
    }

    /**
     * Get model
     * @return __return_getModel_type__ __return_getModel_description__
     */
    public function getModel()
    {
        if (is_null($this->_model)) {
            $modelClass = $this->modelClass;
            $this->_model = new $modelClass;
        }

        return $this->_model;
    }
}
