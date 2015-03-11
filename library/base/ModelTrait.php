<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use teal\db\behaviors\Model as ModelBehavior;

trait ModelTrait
{
    /**
     * @inheritdoc
     */
    public function safeAttributes()
    {
        $safe = parent::safeAttributes();
        foreach ($this->behaviors as $behavior) {
            if ($behavior instanceof ModelBehavior) {
                $safe = array_merge($safe, $behavior->safeAttributes());
            }
        }

        return $safe;
    }
}
