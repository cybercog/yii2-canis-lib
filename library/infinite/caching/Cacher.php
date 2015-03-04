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
use yii\caching\TagDependency;
use yii\caching\DbDependency;

/**
 * Cacher [@doctodo write class description for Cacher].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Cacher extends \infinite\base\Component
{
    public static $component = 'cache';
    /**
     * __method_key_description__.
     *
     * @param __param_key_type__ $key  __param_key_description__
     * @param boolean            $hash __param_hash_description__ [optional]
     *
     * @return __return_key_type__ __return_key_description__
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
            $key = md5(Yii::$app->params['salt'].json_encode($key));
        } else {
            if (is_array($key)) {
                $key[] = Yii::$app->params['salt'];
            } else {
                $key = Yii::$app->params['salt'].$key;
            }
        }

        return $key;
        //return md5(json_encode($key));
    }

    /**
     * Get.
     *
     * @param __param_key_type__ $key __param_key_description__
     *
     * @return __return_get_type__ __return_get_description__
     */
    public static function get($key)
    {
        return Yii::$app->{static::$component}->get(self::key($key));
    }

    /**
     * __method_exists_description__.
     *
     * @param __param_key_type__ $key __param_key_description__
     *
     * @return __return_exists_type__ __return_exists_description__
     */
    public static function exists($key)
    {
        return Yii::$app->{static::$component}->exists(self::key($key));
    }

    /**
     * Set.
     *
     * @param __param_key_type__        $key        __param_key_description__
     * @param __param_value_type__      $value      __param_value_description__
     * @param integer                   $expire     __param_expire_description__ [optional]
     * @param __param_dependency_type__ $dependency __param_dependency_description__ [optional]
     *
     * @return __return_set_type__ __return_set_description__
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
     * __method_chainedDependency_description__.
     *
     * @param array $chain __param_chain_description__ [optional]
     *
     * @return __return_chainedDependency_type__ __return_chainedDependency_description__
     */
    public static function chainedDependency($chain = [], $reusable = true)
    {
        return new ChainedDependency(['dependencies' => $chain, 'reusable' => $reusable]);
    }

    /**
     * __method_dbDependency_description__.
     *
     * @param __param_sql_type__ $sql      __param_sql_description__
     * @param boolean            $reusable __param_reusable_description__ [optional]
     *
     * @return __return_dbDependency_type__ __return_dbDependency_description__
     */
    public static function dbDependency($sql, $reusable = true)
    {
        return new DbDependency(['reusable' => $reusable, 'sql' => $sql]);
    }

    /**
     * __method_groupDependency_description__.
     *
     * @param __param_group_type__    $group    __param_group_description__
     * @param __param_category_type__ $category __param_category_description__ [optional]
     *
     * @return __return_groupDependency_type__ __return_groupDependency_description__
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

    public static function categoryDependency($category, $reusable = true)
    {
        return new TagDependency(['tags' => ['category-'.$category], 'reusable' => $reusable]);
    }

    /**
     * __method_invalidateGroup_description__.
     *
     * @param __param_group_type__ $group __param_group_description__
     */
    public static function invalidateGroup($group)
    {
        TagDependency::invalidate(Yii::$app->{static::$component}, [$group]);
    }

    public static function invalidateCategory($category)
    {
        TagDependency::invalidate(Yii::$app->{static::$component}, ['category-'.$category]);
    }
}
