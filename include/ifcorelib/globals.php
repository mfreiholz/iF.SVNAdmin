<?php
/**
 * Global function for the "ifcorelib".
 * 
 * @author Manuel Freiholz, insaneFactory.com 
 */

/**
 * Gets a message which describes the last occured JSON error.
 * 
 * @param int $code [optional] The last occured JSON error code.
 * @return string
 */
function if_json_last_error_message($code = null)
{
	$code = $code === null ? json_last_error() : $code;
	
	$json_error_message = "";
	switch ($code) {
		case JSON_ERROR_NONE:
			$json_error_message = "No error has occurred.";
			break;

		case JSON_ERROR_DEPTH:
			$json_error_message = "The maximum stack depth has been exceeded.";
			break;

		case JSON_ERROR_STATE_MISMATCH:
			$json_error_message = "Occurs with underflow or with the modes mismatch.";
			break;

		case JSON_ERROR_CTRL_CHAR:
			$json_error_message = "Control character error, possibly incorrectly encoded.";
			break;

		case JSON_ERROR_SYNTAX:
			$json_error_message = "Syntax error.";
			break;

		case JSON_ERROR_UTF8:
			$json_error_message = "Malformed UTF-8 characters, possibly incorrectly encoded.";
			break;
	}
	return $json_error_message;
}

/**
 * Set which encryption method should be used as default from the IF_HtPasswd
 * class.
 * @var string
 */
//define("IF_HtPasswd_DefaultCrypt", "CRYPT"); // Unix only.
//define("IF_HtPasswd_DefaultCrypt", "SHA1");
//define("IF_HtPasswd_DefaultCrypt", "MD5"); // Custom Apache APR1 MD5 hash.

/**
 * Provides whether the string is already encoded in UTF-8, if not
 * it will encode it to UTF-8.
 *
 * @param string $data
 * @return string Encoded with UTF-8.
 */
function if_ensure_utf8_encoding($data)
{
	if (function_exists("mb_detect_encoding"))
	{
		if (mb_detect_encoding($data) == "UTF-8") {
			return $data;
		}
		else {
			return utf8_encode($data);
		}
	}
	return $data;
}

/**
 * Makes sure that the string is not encoded in UTF-8 format. If it is encoded
 * with UTF-8, it will automaticaliy beeing decoded.
 *
 * @param string $data
 * @return string The decoded string
 */
function if_ensure_utf8_decoding($data)
{
	if (function_exists("mb_detect_encoding"))
	{
		if (mb_detect_encoding($data) == "UTF-8")
		{
			return utf8_decode($data);
		}
		else
		{
			return $data;
		}
	}
	return $data;
}

/**
 * Scans the given array for emtpy values and removes them.
 *
 * @param $arr The array to be scanned.
 * @return array The array with no more emtpy values.
 */
function if_array_remove_empty_values(&$arr)
{
	$removeCount = 0;
	$arrCount = count($arr);
	for ($i=0; $i<$arrCount; $i++)
	{
		if (empty($arr[$i]))
		{
			unset($arr[$i]);
			$removeCount++;
		}
	}

	if ($removeCount > 0)
		$arr = array_values($arr);
	return $arr;
}

/**
 * Checks whether the variable is set and not empty.
 * @param(optional) reference to scalar, will be set to 'get' or 'post'
 * @return bool
 */
function check_request_var($varname, &$method = NULL)
{
	if (isset($_POST[$varname])) {
		$method = 'post';
		return true;
	}
	else if (isset($_GET[$varname])) {
		$method = 'get';
		return true;
	}
	return false;
}

function get_request_var( $varname )
{
	$method = null;
	if (check_request_var($varname, $method))
	{
		switch($method)
		{
			case 'get':
				if (is_array($_GET[$varname]))
				{
					if (count($_GET[$varname]) == 1 && empty($_GET[$varname][0]))
					{
						return null;
					}
				}
				return $_GET[$varname];

			case 'post':
				if (is_array($_POST[$varname]))
				{
					if (count($_POST[$varname]) == 1 && empty($_POST[$varname][0]))
					{
						return null;
					}
				}
				return $_POST[$varname];
		}
	}
	return null;
}

function remove_item_by_value( &$arr, $value, $preserve = false )
{
  foreach( $arr as $key=>&$val )
  {
    if( $val == $value )
    {
      unset( $arr[$key] );
    }
  }

  if( $preserve )
  {
    return array_values( $arr );
  }
  return $arr;
}

function if_array_remove_object_element(&$arr, $obj, $compare_property)
{
  foreach ($arr as $key => &$val)
  {
    if (is_object($val))
    {
      if (property_exists($val, $compare_property))
      {
        if ($obj->$compare_property == $val->$compare_property)
        {
          unset($arr[$key]);
        }
      }
      else
      {
        // The object doesn't have the compare_property.
        // ..
      }
    }
    else
    {
      // Element is no object.
      continue;
    }
  }
}

function currentScriptFileName()
{
  $parts = explode("/", $_SERVER["SCRIPT_NAME"]);
  return $parts[count($parts)-1];
}
?>