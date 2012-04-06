<?php
/**
 * Provides funtionality to serialize Key<->Value pairs into a storage file.
 * 
 * @author Manuel Freiholz, insaneFactory.com 
 */
class IF_JsonObjectStorage
{
	/**
	 * Path to storage file.
	 * @var string
	 */
	protected $_filePath = null;
	
	/**
	 * Holds all data.
	 * @var stdClass
	 */
	protected $_data = null;
	
	/**
	 * Creates a new instance of this class and loads data from storage file.
	 * 
	 * @param string $filePath Path to storage file.
	 */
	public function __construct($filePath = null)
	{
		if ($filePath != null) {
			self::load($filePath);
		}
	}
	
	/**
	 * Loads data from storage file.
	 * 
	 * @param string $filePath Path to storage file.
	 * @return bool	Returns TRUE if the file exists and data has been loaded.
	 *				Otherwise returns FALSE.
	 */
	public function load($filePath)
	{
		$this->_filePath = $filePath;
		
		if (!file_exists($filePath)) {
			return false;
		}
		
		$content = file_get_contents($this->_filePath);

		if ($content !== false) {
			$this->_data = json_decode($content, false, 512, 0);
			
			// Data must be an array!
			if ($this->_data !== null) {
				return true;
			}
			else {
				throw new Exception("JSON Parsing Errror: " . if_json_last_error_message());
			}
			
		}
		
		return false;
	}
	
	/**
	 * Saves data to storage file.
	 * 
	 * @param string $filePath Path to storage file.
	 * @return bool	Returns TRUE if the data has been saved to storage file.
	 *				Otherwise returns FALSE on error.
	 */
	public function save($filePath = null)
	{
		$saveFilePath = $filePath != null ? $filePath : $this->_filePath;
		
		$enc_options = 0;
		if (defined("JSON_PRETTY_PRINT")) {
			$enc_options |= JSON_PRETTY_PRINT;
		}
		
		$content = json_encode($this->_data, $enc_options);
		
		if ($content === false) {
			throw new Exception("JSON Encode Error: " . if_json_last_error_message());
		}
		
		if (file_put_contents($saveFilePath, $content) !== false) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Sets data.
	 * 
	 * @param string $key
	 * @param mixed $value 
	 */
	public function setData($key, $value)
	{
		$this->_data->$key = $value;
	}
	
	/**
	 * Gets the data which has been storage as $key. If the data doesn't
	 * exists the function will return NULL.
	 * 
	 * @param string $key 
	 * @return mixed or NULL
	 */
	public function getData($key)
	{
		if (property_exists($this->_data, $key)) {
			return $this->_data->$key;
		}
		return null;
	}
	
	/**
	 * Casts the given object into another.
	 * 
	 * @param object $obj
	 * @param string $className
	 * @return \className|null 
	 */
	public function objectCast($obj, $className)
	{
		if (class_exists($className)) {
			$class = new $className;
			foreach ($obj as $propertyName => $propertyValue) {
				$class->$propertyName = $propertyValue;
			}
			return $class;
		}
		return null;
	}
}