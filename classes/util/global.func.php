<?php
function checkPHPVersion($minimumVersion)
{
  return version_compare(PHP_VERSION, $minimumVersion);
}

function exception_handler($exception)
{
  echo "<b>Error:</b> ".$exception->getMessage()."<br>";
  echo "<pre>".$exception->getTraceAsString()."</pre>";
  exit(1);
}

/**
 * Escapes a value for HTML output (element content and quoted attributes).
 */
function h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escapes text which may already contain escaped parts, e.g. exception
 * messages built with tr() (escaped arguments) or from raw input.
 * The result is escaped exactly once.
 */
function h_once($value)
{
  return h(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

////////////////////////////////////////////////////////////////////////
// CSRF PROTECTION
////////////////////////////////////////////////////////////////////////

/**
 * Gets the CSRF token of the current session, creates it if needed.
 */
function csrf_token()
{
  if (empty($_SESSION['csrf_token']))
  {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

/**
 * Prints the hidden CSRF token field. Must be part of every POST form.
 */
function PrintCsrfField()
{
  print('<input type="hidden" name="csrf_token" value="'.h(csrf_token()).'">');
}

/**
 * Checks that the current request is a POST request with a valid CSRF token.
 * @return bool
 */
function csrf_check()
{
  if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')
    return false;
  if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']))
    return false;
  return hash_equals(csrf_token(), $_POST['csrf_token']);
}

/**
 * Same as csrf_check(), but adds a visible error for the user on failure.
 * @return bool
 */
function csrf_require()
{
  if (csrf_check())
    return true;
  global $appEngine;
  $appEngine->addException(new ValidationException(tr("The form has expired or is invalid. Please try again.")));
  return false;
}

function tr($text, $args=null)
{
  global $appTR;
  return $appTR->tr($text, $args);
}

////////////////////////////////////////////////////////////////////////
// LOGGING
////////////////////////////////////////////////////////////////////////

function if_log_debug($message)
{
	error_log($message);
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

function SetValue($varName, $varValue)
{
	global $appTemplate;
	$appTemplate->addReplacement($varName, $varValue);
}

function GetValue($varName)
{
	global $appTemplate;
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
	$v=NULL;
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

function PrintStringValue($varName) { print(h(GetStringValue($varName))); }
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
