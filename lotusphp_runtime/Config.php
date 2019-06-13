<?php
class LtConfig
{
	public $storeHandle;
	protected $conf;

	public function __construct()
	{
		if (!is_object($this->storeHandle))
		{
			$this->storeHandle = new LtStoreMemory;
		}
	}

	public function init()
	{
		//don't removeme, I am the placeholder
	}

	public function get($key)
	{
		$storedConfig = $this->storeHandle->get($key);
		// 如果存储的配置是表达式配置，则要进行处理
		if ($storedConfig instanceof LtConfigExpression)
		{
			$str = $storedConfig->__toString();
			if ($storedConfig->autoRetrived)
			{
				eval("\$value=$str;");
				return $value;
			}
			else
			{
				return $str;
			}
		}
		else
		{
			return $storedConfig;
		}
	}

	/**
	 * 警告
	 * 这里会包含两个用户定义的配置文件，为了不和配置文件里的变量名发生重名
	 * 本方法不定义和使用变量名
	 */
	public function loadConfigFile($configFile)
	{
	    // store中没有任何配置时才执行
		if (0 == $this->storeHandle->get(".config_total"))
		{
			if (null === $configFile || !is_file($configFile))
			{
				trigger_error("no config file specified or invalid config file");
			}
			// 在此处将$configFile文件的执行结果返回给类变量的conf
			$this->conf = include($configFile);
			if (!is_array($this->conf))
			{
				trigger_error("config file do NOT return array: $configFile");
			}
			elseif (!empty($this->conf))
			{
				if (0 == $this->storeHandle->get(".config_total"))
				{
					$this->storeHandle->add(".config_total", 0);
				}
				$this->addConfig($this->conf);
			}
		}
	}

    /**
     * 循环配置数组，将配置存入store然后，更新store里的配置计数
     * @param $configArray
     */
	public function addConfig($configArray)
	{
		foreach($configArray as $key => $value)
		{
			if (!$this->storeHandle->update($key, $value))
			{
				if ($this->storeHandle->add($key, $value))
				{
					$this->storeHandle->update(".config_total", $this->storeHandle->get(".config_total") + 1, 0);
				}
			}
		}
	}
}
