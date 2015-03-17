<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors;

/**
 * Model [[@doctodo class_description:canis\db\behaviors\Model]].
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
