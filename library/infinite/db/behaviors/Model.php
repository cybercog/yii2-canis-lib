<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

/**
 * Model [[@doctodo class_description:infinite\db\behaviors\Model]].
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
