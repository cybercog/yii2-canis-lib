<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use Yii;

/**
 * Module [@doctodo write class description for Module].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class ModuleSet extends \yii\base\Module
{
    use ObjectTrait;

    /**
     * Get module type.
     */
    abstract public function getSubmodules();

    public function init()
    {
        parent::init();
        foreach ($this->getSubmodules() as $submoduleKey => $submodule) {
            //Yii::$app->modules[$submoduleKey] = $submodule;
            Yii::$app->setModule($submoduleKey, $submodule);
            if (substr($submoduleKey, 0, 3) === 'Set') {
                Yii::$app->getModule($submoduleKey);
            }
        }
    }
}
