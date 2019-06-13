<?php
class LtObjectUtil
{
	static $instances;

    /**
     * 使用关联数组的形式实现单例模式
     * 数组里已经存在，就返回数组内已经定义的元素。数组内不存在，就先定义并存入数组中，然后再返回
     * @param $className
     * @param bool $autoInited
     * @return bool
     */
	static public function singleton($className, $autoInited = true)
	{
		if (empty($className))
		{
			trigger_error('empty class name');
			return false;
		}
		$key = strtolower($className);
		if (isset(self::$instances[$key]))
		{
			return self::$instances[$key];
		}
		else if (class_exists($className))
		{
			$newInstance = new $className;
			if ($autoInited && method_exists($newInstance, 'init'))
			{
				$newInstance->init();
			}
			self::$instances[$key] = $newInstance;
			return $newInstance;
		}
		else
		{
			return false;
		}
	}
}
