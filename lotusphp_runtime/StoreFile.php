<?php
class LtStoreFile implements LtStore
{
	public $storeDir;
	public $prefix = 'LtStore';
	public $useSerialize = false;
	static public $defaultStoreDir = "/tmp/LtStoreFile/";
	public function init()
	{
		/**
		 * 目录不存在和是否可写在调用add是测试
		 * @todo detect dir is exists and writable
		 */
		if (null == $this->storeDir)
		{
			$this->storeDir = self::$defaultStoreDir;
		}
		$this->storeDir = str_replace('\\', '/', $this->storeDir);
		$this->storeDir = rtrim($this->storeDir, '\\/') . '/';
	}

	/**
	 * 当key存在时:
	 * 如果没有过期, 不更新值, 返回 false
	 * 如果已经过期,   更新值, 返回 true
	 * add操作实际上相当于创建一个唯一和key对应的文件，来存储value
	 * @return bool
	 */
	public function add($key, $value)
	{
		$file = $this->getFilePath($key);
		$cachePath = pathinfo($file, PATHINFO_DIRNAME);
		if (!is_dir($cachePath))
		{
			if (!@mkdir($cachePath, 0777, true))
			{
				trigger_error("Can not create $cachePath");
			}
		}
		// 如果文件存在，就说明对应的key已经设置的有值，应该使用update接口，所以返回false
		if (is_file($file))
		{
			return false;
		}
		if ($this->useSerialize)
		{
			$value = serialize($value);
		}
		$length = file_put_contents($file, '<?php exit;?>' . $value);
		return $length > 0 ? true : false;
	}

	/**
	 * 删除不存在的key返回false
	 * del实际上相当于删除和key唯一对应的文件
	 * @return bool
	 */
	public function del($key)
	{
		$file = $this->getFilePath($key);
		if (!is_file($file))
		{
			return false;
		}
		else
		{
			return @unlink($file);
		}
	}

	/**
	 * 取不存在的key返回false
	 * 已经过期返回false
	 *
	 * @return 成功返回数据,失败返回false
	 */
	public function get($key)
	{
		$file = $this->getFilePath($key);
		if (!is_file($file))
		{
			return false;
		}
		$str = file_get_contents($file);
		$value = substr($str, 13);
		if ($this->useSerialize)
		{
			$value = unserialize($value);
		}
		return $value;
	}

	/**
	 * key不存在 返回false
	 * 不管有没有过期,都更新数据
	 *
	 * @return bool
	 */
	public function update($key, $value)
	{
		$file = $this->getFilePath($key);
		// 如果key对应的文件不存在，应该走add接口，所以在此返回false
		if (!is_file($file))
		{
			return false;
		}
		else
		{
			if ($this->useSerialize)
			{
				$value = serialize($value);
			}
			$length = file_put_contents($file, '<?php exit;?>' . $value);
			return $length > 0 ? true : false;
		}
	}

    /**
     * 根据传递的参数key，生成对应的存储文件完整路径
     * 中间使用了MD5获取传参key的散列值，并使用这个散列值的前几位形成目录层级结构
     * 通过将不同的key映射到不同目录位置，减少目录的大小，保证通过key获取文件的快速性
     * 类似于hash table
     * @param $key
     * @return string
     */
	public function getFilePath($key)
	{
		$token = md5($key);
		return $this->storeDir .
		$this->prefix . '/' .
		substr($token, 0, 2) .'/' .
		substr($token, 2, 2) . '/' .
		$token . '.php';
	}
}
