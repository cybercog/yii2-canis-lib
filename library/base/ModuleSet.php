<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use Yii;

/**
 * ModuleSet [[@doctodo class_description:teal\base\ModuleSet]].
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

    /**
     * @inheritdoc
     */
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
