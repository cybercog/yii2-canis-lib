<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\data;

use Yii;

/**
 * ActiveDataProvider [[@doctodo class_description:teal\data\ActiveDataProvider]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    /**
     * @inheritdoc
     */
    public function setPagination($value)
    {
        if (is_array($value) && !isset($config['class'])) {
            $config['class'] = Pagination::className();
        }

        return parent::setPagination($value);
    }

    /**
     * @inheritdoc
     */
    public function setSort($value)
    {
        if (is_array($value)) {
            $config = ['class' => Sort::className()];
            if ($this->id !== null) {
                $config['sortVar'] = $this->id . '-sort';
            }
            $value = Yii::createObject(array_merge($config, $value));
        }

        return parent::setSort($value);
    }
}
