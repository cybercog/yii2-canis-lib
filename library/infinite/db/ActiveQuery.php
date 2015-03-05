<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

use infinite\base\ComponentTrait;

/**
 * ActiveQuery [[@doctodo class_description:infinite\db\ActiveQuery]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
    /**
     * @var [[@doctodo var_type:_model]] [[@doctodo var_description:_model]]
     */
    protected $_model;
    /**
     * Get is aco.
     *
     * @return [[@doctodo return_type:getIsAco]] [[@doctodo return_description:getIsAco]]
     */
    public function getIsAco()
    {
        $class = $this->modelClass;

        return $class::$isAco;
    }

    /**
     * Get model.
     *
     * @return [[@doctodo return_type:getModel]] [[@doctodo return_description:getModel]]
     */
    public function getModel()
    {
        if (is_null($this->_model)) {
            $modelClass = $this->modelClass;
            $this->_model = new $modelClass();
        }

        return $this->_model;
    }
}
