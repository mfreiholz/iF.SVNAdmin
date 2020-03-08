<?php
function checkPHPVersion($minimumVersion)
{
  $phpVersion = phpversion();
  $phpVersionParts = explode(".", $phpVersion);
  $minVersionParts = explode(".", $minimumVersion);
  $minVersionPartsCount = count($minVersionParts);

  $check = true;
  if ($minVersionPartsCount >= 1)
    if ($phpVersionParts[0] < $minVersionParts[0])
      $check = false;

  if ($minVersionPartsCount >= 2 && $phpVersionParts[0] == $minVersionParts[0])
    if ($phpVersionParts[1] < $minVersionParts[1])
      $check = false;

  if ($minVersionPartsCount >= 3 && $phpVersionParts[0] == $minVersionParts[0] && $phpVersionParts[1] == $minVersionParts[1])
    if ($phpVersionParts[2] < $minVersionParts[2])
      $check = false;

  return $check;
}

function exception_handler($exception)
{
  echo "<b>Error:</b> ".$exception->getMessage()."<br>";
  echo "<pre>".$exception->getTraceAsString()."</pre>";
  exit(1);
}

function tr($text, $args=null)
{
  global $appTR;
  return $appTR->tr($text, $args);
}

////////////////////////////////////////////////////////////////////////
// LOGGING
// 记录日志，将日志保存到/var/log/httpd/error_log文件中
////////////////////////////////////////////////////////////////////////
// 普通日志
function if_log_debug($message)
{
	error_log($message);
}

// 将数组变量写入到日志中
function if_log_array($varArray, $message=null)
{
    if_log_debug($message . ':\n');
    if_log_debug(var_export($varArray, true));
}

// ------------------------------------------ Global template "print" functions.

function GlobalHeader()
{
	include_once("pages/global-header.php");
}

function GlobalFooter()
{
	include_once("pages/global-footer.php");
}

function ProcessTemplate($file)
{
	include_once("pages/".$file);
}

function AppVersion()
{
	global $appEngine;
	print($appEngine->getAppVersionString());
}

function SessionUsername()
{
	if (isset($_SESSION["svnadmin_username"]))
	{
		print($_SESSION["svnadmin_username"]);
	}
}

function ScriptName()
{
	print(currentScriptFileName());
}

function PrintApplicationVersion()
{
	global $appEngine;
	print($appEngine->getAppVersionString());
}

function PrintSessionUsername()
{
	if (isset($_SESSION["svnadmin_username"]))
	{
		print($_SESSION["svnadmin_username"]);
	}
}

function PrintCurrentScriptName()
{
	print(currentScriptFileName());
}

// -------------------------------------------------- Global template functions.

function AppEngine()
{
	global $appEngine;
	return $appEngine;
}

function Translate($text, $args=null)
{
	global $appTR;
	print($appTR->tr($text, $args));
}

function CurrentLocale()
{
	if (isset($_COOKIE["locale"]) && !empty($_COOKIE["locale"]))
	{
		return $_COOKIE["locale"];
	}
	return "en_US";
}

// 设置值
function SetValue($varName, $varValue)
{
    if_log_debug('SetValue');
    if_log_debug('SetValue function: $varName: ' . $varName);
    global $appTemplate;

	$appTemplate->addReplacement($varName, $varValue);
}

function GetValue($varName)
{
    if_log_debug('Get Value of :' . $varName);
	global $appTemplate; // 引用全局变量，实质是IF_Template类实例，参考IF_Template.class.php文件

    // 此处的$varName在项目中，可能的取值：RepositoryParentList，ShowOptions，ShowDeleteButton，RepositoryList
    // 通过		$v=$appTemplate->m_replacements[$varName]; 获取到了每种类型时的列表，列表中包含各种的对象
    // 此处是获取数据的关键位置
	if (isset($appTemplate->m_replacements[$varName]))
	{
		$v=$appTemplate->m_replacements[$varName];
		if (!empty($v))
		{
			return $v;
		}
	}
	return NULL;
}

function GetArrayValue($varName)
{
    // 此函数用于获取列表的实际值，可用于多个对象，如：
    // $list = GetArrayValue('RepositoryList');
    // GetArrayValue('RepositoryParentList')
    if_log_debug('Get Arrary Value of :'. $varName);

	$v=NULL;
	// 调用 GetValue函数
	if (($v=GetValue($varName)) != NULL)
	{
		if (is_array($v))
		{
			return $v;
		}
		else
		{
			return array($v);
		}
	}
	return array();
}

function GetStringValue($varName)
{
	$v=NULL;
	if (($v=GetValue($varName)) != NULL)
	{
		return $v;
	}
	return "";
}

function GetIntValue($varName, $base=10)
{
	$v=NULL;
	if (($v=GetValue($varName)) != NULL)
	{
		if (is_int($v))
		{
			return $v;
		}
		else
		{
			return intval($v, $base);
		}
	}
	return 0;
}

function GetBoolValue($varName)
{
	$v=NULL;
	if (($v=GetValue($varName)) != NULL)
	{
		if (is_bool($v))
		{
			return $v;
		}
		else
		{
			return (boolean)$v;
		}
	}
	return false;
}

function PrintStringValue($varName) { print(GetStringValue($varName)); }
function PrintIntValue($varName) { print(GetIntValue($varName)); }
function PrintBoolValue($varName) { print(GetBoolValue($varName)); }

// ---------------------------------------- Global template condition functions.

function IsUserLoggedIn()
{
	global $appEngine;
	return $appEngine->checkUserAuthentication(false);
}

function IsViewUpdateable()
{
	global $appEngine;
	return $appEngine->isViewUpdateable();
}

function IsProviderActive($providerTypeId)
{
	global $appEngine;
	return $appEngine->isProviderActive($providerTypeId);
}

function HasAccess($module, $action)
{
	global $appEngine;
	return $appEngine->checkUserAccess($module, $action);
}

function HasAppExceptions()
{
	global $appEngine;
	$a=$appEngine->getExceptions();
	if (!empty($a))
	{
		return true;
	}
	return false;
}

function HasAppMessages()
{
	global $appEngine;
	$a=$appEngine->getMessages();
	if (!empty($a))
	{
		return true;
	}
	return false;
}

// --------------------------------------------------- Complete html components.

/**
 * Prints the HTML code for a input box which filter a defined table.
 *
 * @param string $tableId The HTML-ID of the table.
 * @param int $columnIndex The column index of the content in which is to search.
 */
function HtmlFilterBox($tableId, $columnIndex=0)
{?>
	<div class="datatablesearch">
		<?php Translate("Filter"); ?>:
	  <input type="text" class="filterbox" onkeyup="filterDataTable('<?php print($tableId); ?>', <?php print($columnIndex); ?>,this.value);">
	</div>
<?php
}
?>