<?php
include("include/config.inc.php");

/**
 * The configuration of the web application.
 * @global $cfgEngine IF_IniFile
 */
$cfgEngine = $appEngine->getConfig();

/**
 * Indicates whether the "setadmin" page should be shown.
 * @global $show_setadmin bool
 */
$show_setadmin = false;

/**
 * Indicates whether an user with the administrator role exists.
 * @global $hasAdminDefined bool
 */
$hasAdminDefined = true;
if ($appEngine->isAclManagerActive())
{
	$hasAdminDefined = $appEngine->getAclManager()->hasAdminDefined();
}

/**
 * Indicates whether it is the first start of the application.
 * @global $isFirstStart bool
 */
$isFirstStart = ($cfgEngine->getValue("Common", "FirstStart") == "1") ? true : false;

$skip_auth = false;
if (!$isFirstStart && !$hasAdminDefined)
{
	$skip_auth = true;
	$show_setadmin = true;
}

if (!$hasAdminDefined || $isFirstStart)
{
	$skip_auth = true;
}

// Check auth?
if (!$skip_auth)
{
	$appEngine->checkUserAuthentication(true, ACL_MOD_SETTINGS, ACL_ACTION_CHANGE);
}

$appTR->loadModule("settings");

////////////////////////////////////////////////////////////////////////////////
// Fetch request parameters.
////////////////////////////////////////////////////////////////////////////////

$pUserViewProviderType = get_request_var("UserViewProviderType");
$pUserEditProviderType = get_request_var("UserEditProviderType");
$pGroupViewProviderType = get_request_var("GroupViewProviderType");
$pGroupEditProviderType = get_request_var("GroupEditProviderType");
$pRepositoryViewProviderType = get_request_var("RepositoryViewProviderType");
$pRepositoryEditProviderType = get_request_var("RepositoryEditProviderType");
$pSVNAuthFile = get_request_var("SVNAuthFile");
$pSVNUserFile = get_request_var("SVNUserFile");
$pSVNUserDigestFile = get_request_var("SVNUserDigestFile");
$pSVNDigestRealm = get_request_var("SVNDigestRealm");
$pSVNParentPath = get_request_var("SVNParentPath");
$pSvnExecutable = get_request_var("SvnExecutable");
$pSvnAdminExecutable = get_request_var("SvnAdminExecutable");
$pLdapHostAddress = get_request_var("LdapHostAddress");
$pLdapProtocolVersion = get_request_var("LdapProtocolVersion");
$pLdapBindDN = get_request_var("LdapBindDN");
$pLdapBindPassword = get_request_var("LdapBindPassword");
$pLdapUserBaseDn = get_request_var("LdapUserBaseDn");
$pLdapUserSearchFilter = get_request_var("LdapUserSearchFilter");
$pLdapUserAttributes = get_request_var("LdapUserAttributes");
$pLdapGroupBaseDn = get_request_var("LdapGroupBaseDn");
$pLdapGroupSearchFilter = get_request_var("LdapGroupSearchFilter");
$pLdapGroupAttributes = get_request_var("LdapGroupAttributes");
$pLdapGroupsToUserAttribute = get_request_var("LdapGroupsToUserAttribute");
$pLdapGroupsToUserAttributeValue = get_request_var("LdapGroupsToUserAttributeValue");

////////////////////////////////////////////////////////////////////////////////
// Reset first start up value.
////////////////////////////////////////////////////////////////////////////////

if (check_request_var("firststart"))
{
  $cfgEngine->setValue("Common", "FirstStart", 1);
  $cfgEngine->saveToFile();

  header("Location: settings.php");
  exit(0);
}

////////////////////////////////////////////////////////////////////////////////
// Save values.
////////////////////////////////////////////////////////////////////////////////

if (check_request_var("save"))
{
	$cfgEngine->setValue("Engine:Providers", "UserViewProviderType", $pUserViewProviderType);
	$cfgEngine->setValue("Engine:Providers", "UserEditProviderType", $pUserEditProviderType);
	$cfgEngine->setValue("Engine:Providers", "GroupViewProviderType", $pGroupViewProviderType);
	$cfgEngine->setValue("Engine:Providers", "GroupEditProviderType", $pGroupEditProviderType);
	$cfgEngine->setValue("Engine:Providers", "RepositoryViewProviderType", $pRepositoryViewProviderType);
	$cfgEngine->setValue("Engine:Providers", "RepositoryEditProviderType", $pRepositoryEditProviderType);
	$cfgEngine->setValue("Subversion", "SVNAuthFile", $pSVNAuthFile);
	$cfgEngine->setValue("Users:passwd", "SVNUserFile", $pSVNUserFile);
	$cfgEngine->setValue("Users:digest", "SVNUserDigestFile", $pSVNUserDigestFile);
	$cfgEngine->setValue("Users:digest", "SVNDigestRealm", $pSVNDigestRealm);
	$cfgEngine->setValue("Repositories:svnclient", "SVNParentPath", $pSVNParentPath);
	$cfgEngine->setValue("Repositories:svnclient", "SvnExecutable", $pSvnExecutable);
	$cfgEngine->setValue("Repositories:svnclient", "SvnAdminExecutable", $pSvnAdminExecutable);
	$cfgEngine->setValue("Ldap", "HostAddress", $pLdapHostAddress);
	$cfgEngine->setValue("Ldap", "ProtocolVersion", $pLdapProtocolVersion);
	$cfgEngine->setValue("Ldap", "BindDN", $pLdapBindDN);
	$cfgEngine->setValue("Ldap", "BindPassword", $pLdapBindPassword);
	$cfgEngine->setValue("Users:ldap", "BaseDN", $pLdapUserBaseDn);
	$cfgEngine->setValue("Users:ldap", "SearchFilter", $pLdapUserSearchFilter);
	$cfgEngine->setValue("Users:ldap", "Attributes", $pLdapUserAttributes);
	$cfgEngine->setValue("Groups:ldap", "BaseDN", $pLdapGroupBaseDn);
	$cfgEngine->setValue("Groups:ldap", "SearchFilter", $pLdapGroupSearchFilter);
	$cfgEngine->setValue("Groups:ldap", "Attributes", $pLdapGroupAttributes);
	$cfgEngine->setValue("Groups:ldap", "GroupsToUserAttribute", $pLdapGroupsToUserAttribute);
	$cfgEngine->setValue("Groups:ldap", "GroupsToUserAttributeValue", $pLdapGroupsToUserAttributeValue);
	$cfgEngine->setValue("Common", "FirstStart", 0);

	// Save configuration now.
	try {
		$b = $cfgEngine->saveToFile();
		if (!$b) {
			throw new Exception("ERROR: " + tr("Could not save configuration. You should check the file permissions."));
		}

		if (!$hasAdminDefined) {
			header("Location: settings.php");
			exit(0);
		}

		$appEngine->addMessage(tr("Done."));
	}
	catch (Exception $except) {
		$appEngine->addException($except);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Do the tests. (Comes in with AJAX)
////////////////////////////////////////////////////////////////////////////////

if (check_request_var("dotest") && check_request_var("dotestsec"))
{
	// Turn off error reporting, we only want return JSON code.
	// Update: Do NOT turn it off. We probably could use register_shutdown_function()
	// for fatal functions but actually its enough that the "error" event on client
	// side dispaly the error output from this php script, if an error occurs.
	// (mostly ldap based..)
	//@error_reporting(0);
	ini_set("display_errors", "Off");

	$msgOk = null;
	$msgErr = null;
	$testSection = get_request_var("dotestsec");

	switch($testSection)
	{
		case "SVNAuthFile":
			if (file_exists($pSVNAuthFile))
				if (is_writable($pSVNAuthFile))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The file exists but is not writable.");
			else
				$msgErr = $appTR->tr("The file does not exist.");
			break;


		case "SVNUserFile":
			if (file_exists($pSVNUserFile))
				if (is_writable($pSVNUserFile))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The file exists but is not writable.");
			else
				$msgErr = $appTR->tr("The file does not exist.");
			break;


		case "SVNUserDigestFile":
			if (file_exists($pSVNUserDigestFile))
				if (is_writable($pSVNUserDigestFile))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The file exists but is not writable.");
			else
				$msgErr = $appTR->tr("The file does not exist.");
			break;


		case "SVNParentPath":
			if (file_exists($pSVNParentPath))
				if (is_writable($pSVNParentPath))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The folder exists but is not writable.");
			else
				$msgErr = $appTR->tr("The folder does not exist.");
			break;


		case "SvnExecutable":
			if (file_exists($pSvnExecutable))
				if (is_executable($pSvnExecutable))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The file exists but is not executable.");
			else
				$msgErr = $appTR->tr("The file does not exist.");
			break;


		case "SvnAdminExecutable":
			if (file_exists($pSvnAdminExecutable))
				if (is_executable($pSvnAdminExecutable))
					$msgOk = $appTR->tr("Test passed.");
				else
					$msgErr = $appTR->tr("The file exists but is not executable.");
			else
				$msgErr = $appTR->tr("The file does not exist.");
			break;


		case "LdapConnection":
			if (IF_AbstractLdapConnector::isLdapExtensionEnabled())
			{
				$connector = new IF_AbstractLdapConnector();
				if ($connector->connect($pLdapHostAddress, 0, $pLdapProtocolVersion))
				{
					if ($connector->bind($pLdapBindDN, $pLdapBindPassword))
						$msgOk = tr("Test passed.");
					else
						$msgErr = tr("Connection failed:")." ".ldap_error($connector->connection);
				}
				else
				{
					$msgErr = tr("Connection failed:")." ".ldap_error($connector->connection);
				}
			}
			else
			{
				$msgErr = tr("PHP LDAP extension is not available.");
			}
			break;


		case "LdapUser":
			if (IF_AbstractLdapConnector::isLdapExtensionEnabled())
			{
				include_once("./classes/providers/ldap/LdapUserViewProvider.class.php");

				$up = new \svnadmin\providers\ldap\LdapUserViewProvider();
				$up->setConnectionInformation($pLdapHostAddress, 0, $pLdapProtocolVersion, $pLdapBindDN, $pLdapBindPassword);
				$up->setUserViewInformation($pLdapUserBaseDn, $pLdapUserSearchFilter, $pLdapUserAttributes);

				if ($up->init() === true)
				{
					$users = $up->getUsers();
					$usersCount = count($users);

					$t = "Found ".$usersCount." users.<br>";

					$doComma = false;
					for ($i=0; $i<$usersCount; $i++)
					{
						if ($doComma)
							$t.= ", ";
						$doComma = true;
						$t.= $users[$i]->name;
					}
					$msgOk = $t;
				}
				else
				{
					$msgErr = tr("Connection failed:")." ".ldap_error($up->connection);
				}
			}
			else
			{
				$msgErr = tr("PHP LDAP extension is not available.");
			}
			break;


		case "LdapGroup":
			if (IF_AbstractLdapConnector::isLdapExtensionEnabled())
			{
				include_once("./classes/providers/ldap/LdapUserViewProvider.class.php");

				$up = new \svnadmin\providers\ldap\LdapUserViewProvider();
				$up->setConnectionInformation($pLdapHostAddress, 0, $pLdapProtocolVersion, $pLdapBindDN, $pLdapBindPassword);
				$up->setGroupViewInformation($pLdapGroupBaseDn, $pLdapGroupSearchFilter, $pLdapGroupAttributes, $pLdapGroupsToUserAttribute, $pLdapGroupsToUserAttributeValue);

				if ($up->init() === true)
				{
					$groups = $up->getGroups();
					$groupsCount = count($groups);

					$t = "Found ".$groupsCount." groups.<br>";

					$doComma = false;
					for ($i=0; $i<$groupsCount; $i++)
					{
						if ($doComma)
							$t.= ", ";
						$doComma = true;
						$t.= $groups[$i]->name;
					}
					$msgOk = $t;
				}
				else
				{
					$msgErr = tr("Connection failed:")." ".ldap_error($up->connection);
				}
			}
			else
			{
				$msgErr = tr("PHP LDAP extension is not available.");
			}
			break;

		default:
			$msgErr = "Invalid request.";
	}

	// Prepare data for JSON response.
	header("Content-Type: application/json");
	$json = new stdClass();
	$json->type = (empty($msgOk) ? "error" : "success");
	$json->message = (empty($msgOk) ? $msgErr : $msgOk);

	$php_error = error_get_last();
	if ($php_error !== null)
	{
		$json->php_error = $php_error;
	}

	print(json_encode($json));
	exit(0);
}

////////////////////////////////////////////////////////////////////////////////
// Set administrator.
////////////////////////////////////////////////////////////////////////////////

if (check_request_var("setadmin") || $show_setadmin)
{
  // Assign the Administrator role.
  if (check_request_var("saveadmin"))
  {
    $selusers = get_request_var("selected_users");

    $oU = new \svnadmin\core\entities\User;
    $oU->name = $selusers[0];

    $oR = new \svnadmin\core\entities\Role;
    $oR->name = "Administrator";

    // Set selected user as Administrator.
	try {
		$b = $appEngine->getAclManager()->assignUserToRole($oU, $oR);
		if (!$b) {
			throw new Exception("ERROR: Can not assign user to role.");
		}

		$b = $appEngine->getAclManager()->save();
		if (!$b) {
			throw new Exception("ERROR: Can not save ACL changes.");
		}

		$appEngine->addMessage(tr("The user has been defined as admin. You can <a href=\"login.php\">login</a> now."));

      	header("Location: index.php");
		exit(0);
	}
	catch (Exception $except) {
		$appEngine->addException($except);
	}
  }

  // Display users, which can be defined as Administrator.
  if ($appEngine->isUserViewActive())
  {
    $users = $appEngine->getUserViewProvider()->getUsers(false);
    $usersCount = count($users);

    // If there are no users, we create one - if possible.
    if (empty($users))
    {
      if ($appEngine->isUserEditActive())
      {
        $u = new \svnadmin\core\entities\User();
        $u->name = "admin";
        $u->password = "admin";

        try {
        	if (!$appEngine->getUserEditProvider()->addUser($u)) {
        		throw new Exception("ERROR: Can not add user.");
        	}

        	if (!$appEngine->getUserEditProvider()->save()) {
        		throw new Exception("ERROR: Can not save changes.");
        	}
        }
        catch (Exception $e) {
        	$appEngine->addException($e);
        }

        $r = new \svnadmin\core\entities\Role;
        $r->name = "Administrator";

        try {
        	if (!$appEngine->getAclManager()->assignUserToRole($u, $r)) {
        		throw new Exception("ERROR: Can not assign Administrator role.");
        	}

        	if (!$appEngine->getAclManager()->save()) {
        		throw new Exception("ERROR: Can not save changes.");
        	}
        }
        catch (Exception $e) {
			$appEngine->addException($e);
        }

		SetValue("DefaultUserCreated", true);
      }
      else
      {
        // Display message. That no user edit provider is defined to create a
        // default admin user.
        SetValue("NoUserEditActive", true);
      }
    }
    // Display user selection.
    else
    {
      usort($users, array('\svnadmin\core\entities\User',"compare"));
      SetValue("UserList", $users);
      SetValue("ShowUserSelection", true);
    }
  }
  else
  {
  	$appEngine->addException(new Exception(tr("You have to define a user view provider.")));
  }

  ProcessTemplate("settings/setadmin.html.php");
  exit(0);
}
////////////////////////////////////////////////////////////////////////////////
// Form values.
////////////////////////////////////////////////////////////////////////////////

// Load template configuration. (Read only!)
$cfgTpl = new \IF_IniFile();
$cfgTpl->loadFromFile("data/config.tpl.ini");

// SVNAuthFile
$svnAuthFile = $cfgEngine->getValue("Subversion","SVNAuthFile");
$svnAuthFileEx = $cfgTpl->getValue("Subversion","SVNAuthFile");
SetValue("SVNAuthFile", $svnAuthFile);
SetValue("SVNAuthFileEx", $svnAuthFileEx);

// UserViewProviderType
$userViewProviderTypes = array(/*"off",*/ "passwd", "digest", "ldap");
array_unshift($userViewProviderTypes, $cfgEngine->getValue("Engine:Providers","UserViewProviderType"));
SetValue("userViewProviderTypes", $userViewProviderTypes);

// UserEditProviderType
$userEditProviderTypes = array("off", "passwd", "digest");
array_unshift($userEditProviderTypes, $cfgEngine->getValue("Engine:Providers","UserEditProviderType"));
SetValue("userEditProviderTypes", $userEditProviderTypes);

// GroupViewProviderType
$groupViewProviderTypes = array("off", "svnauthfile", "ldap");
array_unshift($groupViewProviderTypes, $cfgEngine->getValue("Engine:Providers","GroupViewProviderType"));
SetValue("groupViewProviderTypes", $groupViewProviderTypes);

// GroupEditProviderType
$groupEditProviderTypes = array("off", "svnauthfile");
array_unshift($groupEditProviderTypes, $cfgEngine->getValue("Engine:Providers","GroupEditProviderType"));
SetValue("groupEditProviderTypes", $groupEditProviderTypes);

// RepositoryViewProviderType
$repositoryViewProviderTypes = array("off", "svnclient");
array_unshift($repositoryViewProviderTypes, $cfgEngine->getValue("Engine:Providers","RepositoryViewProviderType"));
$appTemplate->addReplacement("repositoryViewProviderTypes", $repositoryViewProviderTypes);
SetValue("repositoryViewProviderTypes", $repositoryViewProviderTypes);

// RepositoryEditProviderType
$repositoryEditProviderTypes = array("off", "svnclient");
array_unshift($repositoryEditProviderTypes, $cfgEngine->getValue("Engine:Providers","RepositoryEditProviderType"));
SetValue("repositoryEditProviderTypes", $repositoryEditProviderTypes);

// Passwd file.
$svnUserFile = $cfgEngine->getValue("Users:passwd","SVNUserFile");
$svnUserFileEx = $cfgTpl->getValue("Users:passwd","SVNUserFile");
SetValue("SVNUserFile", $svnUserFile);
SetValue("SVNUserFileEx", $svnUserFileEx);

// Digest.
$svnUserDigestFile = $cfgEngine->getValue("Users:digest","SVNUserDigestFile");
$svnUserDigestFileEx = $cfgTpl->getValue("Users:digest","SVNUserDigestFile");
$svnDigestRealm = $cfgEngine->getValue("Users:digest","SVNDigestRealm");
$svnDigestRealmEx = $cfgTpl->getValue("Users:digest","SVNDigestRealm");
SetValue("SVNUserDigestFile", $svnUserDigestFile);
SetValue("SVNUserDigestFileEx", $svnUserDigestFileEx);
SetValue("SVNDigestRealm", $svnDigestRealm);
SetValue("SVNDigestRealmEx", $svnDigestRealmEx);

// Repositories:svnclient
$svnParentPath = $cfgEngine->getValue("Repositories:svnclient","SVNParentPath");
$svnParentPathEx = $cfgTpl->getValue("Repositories:svnclient","SVNParentPath");
$svnExecutable = $cfgEngine->getValue("Repositories:svnclient","SvnExecutable");
$svnExecutableEx = $cfgTpl->getValue("Repositories:svnclient","SvnExecutable");
$svnAdminExecutable = $cfgEngine->getValue("Repositories:svnclient","SvnAdminExecutable");
$svnAdminExecutableEx = $cfgTpl->getValue("Repositories:svnclient","SvnAdminExecutable");
SetValue("SVNParentPath", $svnParentPath);
SetValue("SVNParentPathEx", $svnParentPathEx);
SetValue("SvnExecutable", $svnExecutable);
SetValue("SvnExecutableEx", $svnExecutableEx);
SetValue("SvnAdminExecutable", $svnAdminExecutable);
SetValue("SvnAdminExecutableEx", $svnAdminExecutableEx);

// LDAP connection.
$ldapHostAddress = $cfgEngine->getValue("Ldap","HostAddress");
$ldapHostAddressEx = $cfgTpl->getValue("Ldap","HostAddress");
$ldapProtocolVersion = $cfgEngine->getValue("Ldap","ProtocolVersion");
$ldapProtocolVersionEx = $cfgTpl->getValue("Ldap","ProtocolVersion");
$ldapBindDN = $cfgEngine->getValue("Ldap","BindDN");
$ldapBindDNEx = $cfgTpl->getValue("Ldap","BindDN");
$ldapBindPassword = $cfgEngine->getValue("Ldap","BindPassword");
$ldapBindPasswordEx = $cfgTpl->getValue("Ldap","BindPassword");
SetValue("LdapHostAddress", $ldapHostAddress);
SetValue("LdapHostAddressEx", $ldapHostAddressEx);
SetValue("LdapProtocolVersion", $ldapProtocolVersion);
SetValue("LdapProtocolVersionEx", $ldapProtocolVersionEx);
SetValue("LdapBindDN", $ldapBindDN);
SetValue("LdapBindDNEx", $ldapBindDNEx);
SetValue("LdapBindPassword", $ldapBindPassword);
SetValue("LdapBindPasswordEx", $ldapBindPasswordEx);

// LDAP user provider information.
$ldapUserBaseDn = $cfgEngine->getValue("Users:ldap","BaseDN");
$ldapUserBaseDnEx = $cfgTpl->getValue("Users:ldap","BaseDN");
$ldapUserSearchFilter = $cfgEngine->getValue("Users:ldap","SearchFilter");
$ldapUserSearchFilterEx = $cfgTpl->getValue("Users:ldap","SearchFilter");
$ldapUserAttributes = $cfgEngine->getValue("Users:ldap","Attributes");
$ldapUserAttributesEx = $cfgTpl->getValue("Users:ldap","Attributes");
SetValue("LdapUserBaseDn", $ldapUserBaseDn);
SetValue("LdapUserBaseDnEx", $ldapUserBaseDnEx);
SetValue("LdapUserSearchFilter", $ldapUserSearchFilter);
SetValue("LdapUserSearchFilterEx", $ldapUserSearchFilterEx);
SetValue("LdapUserAttributes", $ldapUserAttributes);
SetValue("LdapUserAttributesEx", $ldapUserAttributesEx);

// LDAP group provider information.
$ldapGroupBaseDn = $cfgEngine->getValue("Groups:ldap","BaseDN");
$ldapGroupBaseDnEx = $cfgTpl->getValue("Groups:ldap","BaseDN");
$ldapGroupSearchFilter = $cfgEngine->getValue("Groups:ldap","SearchFilter");
$ldapGroupSearchFilterEx = $cfgTpl->getValue("Groups:ldap","SearchFilter");
$ldapGroupAttributes = $cfgEngine->getValue("Groups:ldap","Attributes");
$ldapGroupAttributesEx = $cfgTpl->getValue("Groups:ldap","Attributes");
$ldapGroupsToUserAttribute = $cfgEngine->getValue("Groups:ldap","GroupsToUserAttribute");
$ldapGroupsToUserAttributeEx = $cfgTpl->getValue("Groups:ldap","GroupsToUserAttribute");
$ldapGroupsToUserAttributeValue = $cfgEngine->getValue("Groups:ldap","GroupsToUserAttributeValue");
$ldapGroupsToUserAttributeValueEx = $cfgTpl->getValue("Groups:ldap","GroupsToUserAttributeValue");
SetValue("LdapGroupBaseDn", $ldapGroupBaseDn);
SetValue("LdapGroupBaseDnEx", $ldapGroupBaseDnEx);
SetValue("LdapGroupSearchFilter", $ldapGroupSearchFilter);
SetValue("LdapGroupSearchFilterEx", $ldapGroupSearchFilterEx);
SetValue("LdapGroupAttributes", $ldapGroupAttributes);
SetValue("LdapGroupAttributesEx", $ldapGroupAttributesEx);
SetValue("LdapGroupsToUserAttribute", $ldapGroupsToUserAttribute);
SetValue("LdapGroupsToUserAttributeEx", $ldapGroupsToUserAttributeEx);
SetValue("LdapGroupsToUserAttributeValue", $ldapGroupsToUserAttributeValue);
SetValue("LdapGroupsToUserAttributeValueEx", $ldapGroupsToUserAttributeValueEx);

// Process template.
ProcessTemplate("settings/backend.html.php");
?>