<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\caching;

use Yii;

use yii\caching\ChainedDependency;
use yii\caching\GroupDependency;
use yii\caching\DbDependency;

class Cacher extends \infinite\base\Component
{
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
            $key = md5(json_encode($key));
        }

        return $key;
        //return md5(json_encode($key));
    }

    public static function get($key)
    {
        return Yii::$app->cache->get(self::key($key));
    }

    public static function exists($key)
    {
        return Yii::$app->cache->exists(self::key($key));
    }

    public static function set($key, $value, $expire = 0, $dependency = null)
    {
        $chain = [];
        $chain[] = new GroupDependency(['group' => 'all']);
        if (!is_null($dependency)) {
            $chain[] = $dependency;
        }

        return Yii::$app->cache->set(self::key($key), $value, $expire, static::chainedDependency($chain));
    }

    public static function chainedDependency($chain = [])
    {
        return new ChainedDependency(['dependencies' => $chain]);
    }

    public static function dbDependency($sql, $reusable = false)
    {
        return new DbDependency(['reusable' => $reusable, 'sql' => $sql]);
    }

    public static function groupDependency($group, $category = null)
    {
        if (!is_null($category)) {
            $chain = [];
            $chain[] = new GroupDependency(['group' => ['category', $category]]);
            $chain[] = new GroupDependency(['group' => $group]);

            return static::chainedDependency($chain);
        } else {
            return new GroupDependency(['group' => $group]);
        }
    }

    public static function invalidateGroup($group)
    {
        GroupDependency::invalidate(Yii::$app->cache, $group);
    }
}
