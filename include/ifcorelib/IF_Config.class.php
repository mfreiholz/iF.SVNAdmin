<?php
class IF_SyntaxErrorException extends Exception {}

/**
 * This class provides parsing and writing functionality for INI files.
 * Same as the IF_IniFile class but an improved version.
 *
 * This class does not remove comments or other customized stuff in
 * configuration (*.ini) files on writing changes to disc.
 *
 * @todo Write back empty sections.
 *
 * @author Manuel Freiholz, insaneFactory.com
 *
 * IF_Config类用于提供更高组的解析和读功能
 */
class IF_Config
{
	/**
	 * Path to config file.
	 * @var string
     *
     * 配置文件路径
	 */
	private $configFilePath = null;

	/**
	 * Holds the configuration keys from file.
	 * e.g.: $items["section_name"]["key"] = "value";
	 * @var array()
     *
     * 配置文件键值对
	 */
	private $items = array();

	/**
	 * Constructor.
	 *
	 * @param string $configFilePath Path to config file.
	 */
	public function __construct($configFilePath)
	{
		$this->configFilePath = $configFilePath;
		$this->parse();
	}

	/**
	 * Parses the file in INI format.
	 *
	 * @throws IF_SyntaxErrorException
     *
     * 以ini格式解析配置文件
	 */
	private function parse()
	{
		if (!file_exists($this->configFilePath) || !is_file($this->configFilePath)) {
			throw new Exception('Config file does not exist. ' . $this->configFilePath);
		}

		$fh = fopen($this->configFilePath, 'r');
		// 锁定文件，LOCK_SH表示共享锁定
		if (!flock($fh, LOCK_SH )) {
			throw new Exception('Can not lock (shared) file. ' . $this->configFilePath);
		}
        // 测试，引入引擎
        $engine = \svnadmin\core\Engine::getInstance();

		$last_section_name = NULL;
		// 当前文件指针不是文件结尾处时，读取一行，文件指针会自动移动到下一行
		while (!feof($fh)) {
			$line = fgets($fh);
			$line = trim($line); // 移除行首和行末的whitespace字符，如空格、tab等

			// skip empty lines
            // 忽略空行
			if (empty($line)) {
				continue;
			}
    
			// skip comments
            // 忽略备注信息
			if (strpos($line, '#') === 0
				|| strpos($line, ';') === 0) {
				continue;
			}

			// section header
            // 处理节点头部
			if (substr($line, 0, 1) == '[' ) {
			    // [groups]或[testrepo:/]，原始获取的section_name的值是groups或testrepo:/
//				$section_name = substr($line, 1, strlen($line)-2);
//				$this->items[$section_name] = array();
//				$last_section_name = $section_name;

                // 添加描述信息

				// 因为要把仓库备注信息添加到仓库section_name后面，因此改写此处实现
                // 改造后，section节点示例：
                // [testrepo:/] # desc: 测试仓
                // 则    section_name = "testrepo:/"
                //      section_description = "测试仓"
                $splits = explode('#', $line, 2);
                $section_string = trim($splits[0]);
                $section_comment = trim($splits[1]);
                $section_name = substr($section_string, 1, strlen($section_string)-2);
                $section_description = trim(substr($section_comment, 5, strlen($section_comment)-2));
                $this->items[$section_name] = array();
                $this->items[$section_name]['#section_desc'] = $section_description;
                $last_section_name = $section_name;
                if_log_debug('section_name:' . $section_name . 'section_comment:' . $section_comment);
				continue;
			}
			// "key=value" pairs of last section header
            // 获取键值对，
			else {
			    // 以等号=作为分隔符，对每一行进行拆分，拆分一次，数组包含两个元素
				$splits = explode('=', $line, 2);
				$key = trim($splits[0]); // '用户名'，或者'@组名'
				$val = NULL; // 获取用户或组的权限，不要在权限后面添加备注信息

				if (count($splits) > 1) {
					$val = trim($splits[1]);
				}
				$this->items[$last_section_name][$key] = $val;

			}
		}

		flock($fh, LOCK_UN);
		fclose($fh);
		return true;
	}

	/**
	 * Saves the internal hold configuration to disk.
	 * How? First, read the configuration file and save them
	 * into a buffer, but replace the changed configuration values.
	 * Then write it back to file.
	 *
	 * @param string Path to the file where the config should be saved.
	 *
	 * @return bool
	 */
	public function save($path = null)
	{
		if (!is_array($this->items)) {
			return false;
		}
	
		if ($path == null) {
			$path = $this->configFilePath;
		}

		if (!file_exists($path)) {
			// try to create the file
			if (!touch($path)) {
				throw new Exception('File does not exist and can not create it. ' . $path);
			}
		}

		$fh = fopen($path, 'w');
		flock($fh, LOCK_EX);

		if_log_debug('将$items对象写入到配置文件中');
		// iterate all sections
		foreach ($this->items as $section_name => $section_data) {

//		    fwrite($fh, "\n[" . $section_name . "]\n");
            // 改造此处，将section_description信息写入到配置文件
            fwrite($fh, "\n[" . $section_name . "]");
            if (empty($section_data['#section_desc'])) {
                fwrite($fh, "\n");
            }
            else {
                fwrite($fh, " # desc: " . $section_data['#section_desc'] . "\n");
            }

            // 在列表中加入了'#section_desc'键后，$section_data永远都是列表
			if (is_array($section_data)) {
				// iterate key/value pairs of section
				foreach ($section_data as $key => $val) {
				    // 排除section节点描述信息
				    if ($key !== '#section_desc') {
                        fwrite($fh, $key . '=' . $val . "\n");
                    }
				}
			}
		}
		
		flock($fh, LOCK_UN);
		fclose($fh);
		return true;
	}

	/**
	 * Gets the path to the used config file.
	 *
	 * @return string
	 */
	public function getConfigPath()
	{
		return $this->configFilePath;
	}

	/**
	 * Gets a specified value from config file.
	 *
	 * @param string $section
	 * @param string $key
	 * @param string $defaultValue (default=null)
	 *
	 * @return string (value specified by $defaultValue)
	 */
	public function getValue($section, $key, $defaultValue=null)
	{
		if (isset($this->items[$section]) && isset($this->items[$section][$key]))
		{
			return $this->items[$section][$key];
		}
		return $defaultValue;
	}

	/**
	 * Gets all existing sections from config file.
	 *
	 * @return array<string>
	 */
	public function getSections()
	{
		$ret = array();
		foreach ($this->items as $section => &$noval)
		{
			$ret[] = $section;
		}
		return $ret;
	}

	/**
	 * Gets all existing keys from a specific section.
	 *
	 * @param string $section
	 *
	 * @return array<string>
	 */
	public function getSectionKeys($section)
	{
		if (isset($this->items[$section]))
		{
			if (is_array($this->items[$section]))
			{
				return array_keys($this->items[$section]);
			}
			else
			{
				// Empty section.
				return array();
			}
		}
		else
		{
			// Unknown section.
			return array();
		}
	}

	/**
	 * Sets a specific value to config.
	 * 设置配置文件中的键值对
	 * @param string $section
	 * @param string $key
	 * @param string $value
	 */
	public function setValue($section, $key, $value)
	{
		if (!isset($this->items[$section]))
		{
			$this->items[$section] = array();
		}

		if (!empty($key))
		{
			$this->items[$section][$key] = $value;
		}
	}

	/**
	 * Removes a specific value from config.
	 *
	 * @param string $section
	 * @param string $key
	 */
	public function removeValue($section, $key)
	{
		if (!isset($this->items[$section]))
		{
			return true;
		}

		if (empty($key))
		{
			unset($this->items[$section]);
		}
		else
		{
			if (!isset($this->items[$section][$key]))
			{
				return true;
			}
			unset($this->items[$section][$key]);
		}
		return true;
	}

	/**
	 * Gets to know whether a specific section exists.
	 *
	 * @param string $section
	 *
	 * @return bool
	 */
	public function getSectionExists($section)
	{
		return (isset($this->items[$section]));
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $section
	 * @param unknown_type $key
	 */
	public function getValueExists($section, $key)
	{
		if (isset($this->items[$section]))
		{
			if (isset($this->items[$section][$key]))
			{
				return true;
			}
		}
		return false;
	}
}
?>