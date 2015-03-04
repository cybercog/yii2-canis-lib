<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use infinite\db\behaviors\Model as ModelBehavior;

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
