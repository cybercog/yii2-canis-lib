<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors;

/**
 * Model [[@doctodo class_description:teal\db\behaviors\Model]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Model extends \yii\base\Behavior
{
    /**
     * [[@doctodo method_description:safeAttributes]].
     *
     * @return [[@doctodo return_type:safeAttributes]] [[@doctodo return_description:safeAttributes]]
     */
    public function safeAttributes()
    {
        return [];
    }
}
