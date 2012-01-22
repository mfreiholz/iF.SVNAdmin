<?php
class IF_StringUtils
{
	/**
	 * Resolves the variables of the given <code>$stringValue</code> to the
	 * values of the given array.
	 *
	 * Example string: "Hello %1! How are you today on the %2?"
	 * Example array: [0]=>"User", [1]=>"01.01.1990"
	 *
	 * @param string $stringValue
	 * @param array $args
	 * @return string
	 */
	public static function arguments($stringValue, array $args)
	{
		$matches = array();
		$cnt = preg_match_all("/[^\\\]%(\d+)/", $stringValue, $matches);

		if ($cnt === FALSE)
		{
			return $stringValue;
		}

		for ($i = 0; $i < $cnt; ++$i)
		{
			$argId = $matches[1][$i];
			$argIdIndex = intval($argId) - 1;

			if (isset($args[$argIdIndex]))
			{
				$stringValue = str_replace("%".$argId, $args[$argIdIndex], $stringValue);
			}
		}
		return $stringValue;
	}
}
?>