<?php
namespace infinite\caching;

use Yii;

class Cacher extends \infinite\base\Component {
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
		return Yii::$app->cache->set(self::key($key), $value, $expire, $dependency);
	}
}
?>