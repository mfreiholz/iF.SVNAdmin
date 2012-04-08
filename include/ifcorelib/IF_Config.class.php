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
 */
class IF_Config
{
	/**
	 * Path to config file.
	 * @var string
	 */
	private $configFilePath = null;

	/**
	 * Holds the configuration keys from file.
	 * e.g.: $items["section_name"]["key"] = "value";
	 * @var array()
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
		self::parse();
	}

	/**
	 * Parses the file in INI format.
	 *
	 * @throws IF_SyntaxErrorException
	 */
	private function parse()
	{
		if (!file_exists($this->configFilePath))
		{
			// Config file doesn't exist.
			return false;
		}

		$fh = fopen($this->configFilePath, 'r');

		if (!$fh)
		{
			// Can't open file.
			return false;
		}

		// Insert default '' section.
		$this->items[""] = array();

		// Read line-by-line.
		$current_section = "";
		while (($buffer = fgets($fh, 4096)) !== FALSE)
		{
			// Skip comments.
			if (strpos($buffer, '#') === 0 || strpos($buffer, ';') === 0)
			{
				continue;
			}

			// Section header.
			if (substr($buffer, 0, 1) == '[')
			{
				$end_pos = strpos($buffer, ']');
				if ($end_pos !== FALSE)
				{
					$section_name = substr($buffer, 1, $end_pos-1);
					$current_section = trim($section_name);
					$this->items[$current_section] = array();
					continue;
				}
				else
				{
					throw new IF_SyntaxErrorException("Close tag ']' of section header missing => ".trim($buffer));
				}
			}

			// Key=Value pairs.
			// Note: We need to "trim()" the value, because of the line-break.
			if (preg_match('/^(.*?)=(.*?)$/', $buffer, $matches))
			{
				$key = trim($matches[1]);
				$value = trim($matches[2]);
				$this->items[$current_section][$key] = $value;
			}
		}

		if (!feof($fh))
		{
			// Reading has been canceled unexpected!
			return false;
		}

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
		if (empty($path))
		{
			$path = $this->configFilePath;
		}

		if (!file_exists($path))
		{
			// Config file doesn't exist.
			//return false;
		}

		$fh = fopen($this->configFilePath, 'r');
		if (!$fh)
		{
			// Can not open file.
			//return false;
		}

		// Arrays of items which has been written.
		// e.g.: array( "section_name" => array("key1", "key2") )
		$written_items = array();
		$written_items[""] = array();

		// Read line-by-line.
		$nc = '';
		$current_section = "";
		while (($buffer = fgets($fh, 4096)) !== FALSE)
		{
			// Skip comments.
			if (strpos($buffer, '#') === 0 || strpos($buffer, ';') === 0)
			{
				// Do not save comments of a removed section.
				if (!isset($this->items[$current_section]))
				{
					continue;
				}

				$nc.= $buffer;
			}
			// Section header.
			else if (substr($buffer, 0, 1) == '[')
			{
				$end_pos = strpos($buffer, ']');
				if ($end_pos !== FALSE)
				{
					// Before we change the to the new section header,
					// we need to prove whether there are any new key
					// items for the previous section.
					if ($current_section != '' && isset($this->items[$current_section]))
					{
						foreach ($this->items[$current_section] as $key => $value)
						{
							if (!in_array($key, $written_items[$current_section]))
							{
								// New config key for the previous section.
								$nc.= $key.'='.$this->items[$current_section][$key]."\n";
								$written_items[$current_section][] = $key;
							}
						}
					}

					// Switch to new section now.
					$section_name = substr($buffer, 1, $end_pos-1);
					$current_section = $section_name;

					// Has the section been removed?
					if (!isset($this->items[$current_section]))
					{
						continue;
					}

					$nc.= $buffer;
					$written_items[$current_section] = array();
				}
				else
				{
					throw new IF_SyntaxErrorException("Close tag ']' of section header missing => ".trim($buffer));
				}
			}
			// Key=Value pairs.
			// Note: We need to "trim()" the value, because of the line-break.
			else if (preg_match('/^(.*?)=(.*?)$/', $buffer, $matches))
			{
				$key = trim($matches[1]);
				$value = trim($matches[2]);

				// Write new value to cfg file, if value changed.
				if (isset($this->items[$current_section][$key]) /*&& ($this->items[$current_section][$key] != trim($value))*/)
				{
					$nc.= $key.'='.$this->items[$current_section][$key]."\n";
					$written_items[$current_section][] = $key;
				}
				// Key/Value pair has been removed.
				else
				{
					//$nc.= $buffer;
				}
			}
			else
			{
				$nc.= $buffer;
			}

		} // while (readline)

		if (!feof($fh))
		{
			// Reading has been canceled unexpected!
			fclose($fh);
			return false;
		}
		fclose($fh);


		// Block copied from content while loop.
		// Especially if the last section in file gets new config entry.
		if ($current_section != '' && isset($this->items[$current_section]))
		{
			foreach ($this->items[$current_section] as $key => $value)
			{
				if (!in_array($key, $written_items[$current_section]))
				{
					// New config key for the previous section.
					$nc.= $key.'='.$this->items[$current_section][$key]."\n";
					$written_items[$current_section][] = $key;
				}
			}
		}


		// Check for new added sections.
		foreach ($this->items as $section => $kvarray)
		{
			if (!isset($written_items[$section]))
			{
				// Append the section + KV-pairs.
				$nc.= "\n";
				$nc.= '['.$section.']';
				$nc.= "\n";
				$written_items[$section] = array();

				foreach ($kvarray as $key => $value)
				{
					$nc.= $key.'='.$value."\n";
					$written_items[$section][] = $key;
				}
			}
		}


		// Write configuration.
		$fh = fopen($path, 'w+');
		fwrite($fh, $nc);
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
	 *
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