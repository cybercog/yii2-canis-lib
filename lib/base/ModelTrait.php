<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use canis\db\behaviors\Model as ModelBehavior;

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
