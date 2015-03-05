<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\caching;

use Yii;
use yii\caching\ChainedDependency;
use yii\caching\DbDependency;
use yii\caching\TagDependency;

/**
 * Cacher [[@doctodo class_description:infinite\caching\Cacher]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Cacher extends \infinite\base\Component
{
    /**
     * @var [[@doctodo var_type:component]] [[@doctodo var_description:component]]
     */
    public static $component = 'cache';
    /**
     * [[@doctodo method_description:key]].
     *
     * @param boolean $hash [[@doctodo param_description:hash]] [optional]
     *
     * @return [[@doctodo return_type:key]] [[@doctodo return_description:key]]
     */
    public static function key($key, $hash = false)
    {
        if (is_array($key) && !empty($key['context'])) {
            $context = $key['context'];
            if (!is_array($context)) {
                $context = [$context];
            }
            unset($key['context']);
            $key = ['key' => $key];
            foreach ($context as $value) {
                switch ($value) {
                    case 'user':
                        $userId = null;
                        if (isset(Yii::$app->user->id)) {
                            $userId = Yii::$app->user->id;
                        }
                        $key['user'] = $userId;
                    break;
                    case 'object':
                        $objectId = null;
                        if (isset(Yii::$app->request->object->primaryKey)) {
                            $objectId = Yii::$app->request->object->primaryKey;
                        }
                        $key['object'] = $objectId;
                    break;
                }
            }
        }
        if ($hash) {
            $key = md5(Yii::$app->params['salt'] . json_encode($key));
        } else {
            if (is_array($key)) {
                $key[] = Yii::$app->params['salt'];
            } else {
                $key = Yii::$app->params['salt'] . $key;
            }
        }

        return $key;
        //return md5(json_encode($key));
    }

    /**
     * Get.
     *
     * @return [[@doctodo return_type:get]] [[@doctodo return_description:get]]
     */
    public static function get($key)
    {
        return Yii::$app->{static::$component}->get(self::key($key));
    }

    /**
     * [[@doctodo method_description:exists]].
     *
     * @return [[@doctodo return_type:exists]] [[@doctodo return_description:exists]]
     */
    public static function exists($key)
    {
        return Yii::$app->{static::$component}->exists(self::key($key));
    }

    /**
     * Set.
     *
     * @param integer $expire [[@doctodo param_description:expire]] [optional]
     *
     * @return [[@doctodo return_type:set]] [[@doctodo return_description:set]]
     */
    public static function set($key, $value, $expire = 0, $dependency = null)
    {
        $chain = [];
        $chain[] = new TagDependency(['tags' => 'all']);
        if (!is_null($dependency)) {
            $chain[] = $dependency;
        }

        return Yii::$app->{static::$component}->set(self::key($key), $value, $expire, static::chainedDependency($chain));
    }

    /**
     * [[@doctodo method_description:chainedDependency]].
     *
     * @param array   $chain    [[@doctodo param_description:chain]] [optional]
     * @param boolean $reusable [[@doctodo param_description:reusable]] [optional]
     *
     * @return [[@doctodo return_type:chainedDependency]] [[@doctodo return_description:chainedDependency]]
     */
    public static function chainedDependency($chain = [], $reusable = true)
    {
        return new ChainedDependency(['dependencies' => $chain, 'reusable' => $reusable]);
    }

    /**
     * [[@doctodo method_description:dbDependency]].
     *
     * @param boolean $reusable [[@doctodo param_description:reusable]] [optional]
     *
     * @return [[@doctodo return_type:dbDependency]] [[@doctodo return_description:dbDependency]]
     */
    public static function dbDependency($sql, $reusable = true)
    {
        return new DbDependency(['reusable' => $reusable, 'sql' => $sql]);
    }

    /**
     * [[@doctodo method_description:groupDependency]].
     *
     * @param boolean $reusable [[@doctodo param_description:reusable]] [optional]
     *
     * @return [[@doctodo return_type:groupDependency]] [[@doctodo return_description:groupDependency]]
     */
    public static function groupDependency($group, $category = null, $reusable = true)
    {
        if (!is_null($category)) {
            $chain = [];
            $chain[] = static::categoryDependency($category, $reusable);
            $chain[] = new TagDependency(['tags' => [$group], 'reusable' => $reusable]);

            return static::chainedDependency($chain);
        } else {
            return new TagDependency(['tags' => [$group], 'reusable' => $reusable]);
        }
    }

    /**
     * [[@doctodo method_description:categoryDependency]].
     *
     * @param boolean $reusable [[@doctodo param_description:reusable]] [optional]
     *
     * @return [[@doctodo return_type:categoryDependency]] [[@doctodo return_description:categoryDependency]]
     */
    public static function categoryDependency($category, $reusable = true)
    {
        return new TagDependency(['tags' => ['category-' . $category], 'reusable' => $reusable]);
    }

    /**
     * [[@doctodo method_description:invalidateGroup]].
     */
    public static function invalidateGroup($group)
    {
        TagDependency::invalidate(Yii::$app->{static::$component}, [$group]);
    }

    /**
     * [[@doctodo method_description:invalidateCategory]].
     */
    public static function invalidateCategory($category)
    {
        TagDependency::invalidate(Yii::$app->{static::$component}, ['category-' . $category]);
    }
}
