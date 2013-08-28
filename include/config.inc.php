<?php
/**
 * iF.SVNAdmin
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
error_reporting(E_ALL);
include_once("./classes/util/global.func.php");
set_exception_handler('exception_handler');

// Check PHP version.
if (!checkPHPVersion("5.3")) {
  echo "Wrong PHP version. The minimum required version is: 5.3";
  exit(1);
}

// Does the config.ini file exists?
if (!file_exists("./data/config.ini"))
{
  if (!copy("./data/config.tpl.ini", "./data/config.ini"))
    throw new Exception("Could not copy configuration file template. Require write permission (777) to \"data\" folder and all containing files.");
  header("Location: settings.php");
  exit(0);
}

/**
 * Password encryption type for BASIC authentication.
 */
//define("IF_HtPasswd_DefaultCrypt", "CRYPT"); // Unix only.
//define("IF_HtPasswd_DefaultCrypt", "SHA1");
//define("IF_HtPasswd_DefaultCrypt", "MD5"); // Custom Apache APR1 MD5 hash.

/**
 * PHP/Subversion bug workarround for:
 * Custom config-directory for subversion.
 */
//define("IF_SVNBaseC_ConfigDir", "E:/Development/Test/temp svnadmin/svn-config-dir");

// The iF.CoreLib
$ifcorelib_path = "./include/ifcorelib/";
include_once($ifcorelib_path."globals.php");
include_once($ifcorelib_path."IF_StringUtils.class.php");
include_once($ifcorelib_path."IF_IniFile.func.php");	// TODO: Remove include.
include_once($ifcorelib_path."IF_IniFile.class.php");	// TODO: Remove include.
include_once($ifcorelib_path."IF_Config.class.php");
include_once($ifcorelib_path."IF_SVNBaseC.class.php");
include_once($ifcorelib_path."IF_SVNAuthFileC.class.php");
include_once($ifcorelib_path."IF_SVNClientC.class.php");
include_once($ifcorelib_path."IF_File.class.php");
include_once($ifcorelib_path."IF_Translator.class.php");
include_once($ifcorelib_path."IF_Template.class.php");
include_once($ifcorelib_path."IF_AbstractLdapConnector.class.php");
include_once($ifcorelib_path."IF_SVNAdminC.class.php");
include_once($ifcorelib_path."IF_ACLModule.class.php");
include_once($ifcorelib_path."IF_ACLRole.class.php");
include_once($ifcorelib_path."IF_ACL.class.php");

// Core interfaces and classes.
include_once( "./classes/core/entities/Permission.class.php" );
include_once( "./classes/core/entities/Group.class.php" );
include_once( "./classes/core/entities/User.class.php" );
include_once( "./classes/core/entities/AccessPath.class.php" );
include_once( "./classes/core/entities/Repository.class.php" );
include_once( "./classes/core/entities/RepositoryPath.class.php" );
include_once( "./classes/core/entities/RepositoryParent.class.php" );
include_once( "./classes/core/entities/Role.class.php" );
include_once( "./classes/core/interfaces/IProvider.iface.php" );
include_once( "./classes/core/interfaces/IAuthenticator.iface.php" );
include_once( "./classes/core/interfaces/IViewProvider.iface.php" );
include_once( "./classes/core/interfaces/IEditProvider.iface.php" );
include_once( "./classes/core/interfaces/IGroupEditProvider.iface.php" );
include_once( "./classes/core/interfaces/IGroupViewProvider.iface.php" );
include_once( "./classes/core/interfaces/IPathsEditProvider.iface.php" );
include_once( "./classes/core/interfaces/IPathsViewProvider.iface.php" );
include_once( "./classes/core/interfaces/IUserEditProvider.iface.php" );
include_once( "./classes/core/interfaces/IUserViewProvider.iface.php" );
include_once( "./classes/core/interfaces/IRepositoryEditProvider.iface.php" );
include_once( "./classes/core/interfaces/IRepositoryViewProvider.iface.php" );
include_once( "./classes/core/interfaces/IAclManager.iface.php" );
include_once( "./classes/core/Engine.class.php" );
include_once( "./classes/core/Exceptions.class.php" );

/**
 * iF.SVNAdmin version.
 */
define("MAJOR_VERSION", "1");
define("MINOR_VERSION", "6.2");
define("VERSION_EXTRA", "");

/**
 * Constant ACL modules.
 */
define("ACL_MOD_BASIC",                "basics");
define("ACL_MOD_REPO",                 "repositories");
define("ACL_MOD_USER",                 "users");
define("ACL_MOD_GROUP",                "groups");
define("ACL_MOD_ACCESSPATH",           "accesspaths");
define("ACL_MOD_ROLE",                 "roles");
define("ACL_MOD_SETTINGS",             "settings");
define("ACL_MOD_UPDATE",               "updates");
define("ACL_MOD_PROJECTMANAGER",       "projectmanagers");

/**
 * Constant ACL actions.
 */
define("ACL_ACTION_VIEW",				"view");
define("ACL_ACTION_ADD",				"add");
define("ACL_ACTION_DELETE",				"delete");
define("ACL_ACTION_ASSIGN",				"assign");
define("ACL_ACTION_UNASSIGN",			"unassign");
define("ACL_ACTION_LOGIN",				"login");			// ACL_MOD_BASIC only!
define("ACL_ACTION_CHANGEPASS",			"changepass");		// ACL_MOD_USER only!
define("ACL_ACTION_CHANGEPASS_OTHER",	"changepassother");	// ACL_MOD_USER only!
define("ACL_ACTION_SYNCHRONIZE",		"synchronize");		// ACL_MOD_UPDATE only!
define("ACL_ACTION_CHANGE",				"change");			// ACL_MOD_SETTINGS only (atm...)!
define("ACL_ACTION_DUMP",				"dump");			// ACL_MOD_REPO

/*
 * Switch current locale procecure.
 */
$requestedLocale = get_request_var("locale");
if ($requestedLocale != null)
{
  $_COOKIE["locale"] = $requestedLocale;
  @setcookie("locale", $requestedLocale, time()+60*60*24*365); // 365 days
}

/**
 * The variable is a global variable and can be called in each class
 * and function which are used with this application.
 *
 * @var $appEngine \svnadmin\core\Engine
 * @global $appEngine \svnadmin\core\Engine
 * @deprecated No longer use this global variable!
 */
$appEngine = \svnadmin\core\Engine::getInstance();

/**
 * Global object to translate strings into different languages.
 *
 * @global IF_Translator
 */
$appTR = \IF_Translator::getInstance();
$appTR->setTranslationDirectory($appEngine->getConfig()->getValue("Translation", "Directory"));

/**
 * The template of the current opened page.
 * It supports translations and ACL's.
 *
 * @global IF_Template
 */
$appTemplate = new \IF_Template;


$cfg = $appEngine->getConfig();

/**
 * User view provider.
 */
if ($cfg->getValue("Engine:Providers", "UserViewProviderType") == "passwd")
{
  include_once($ifcorelib_path."IF_HtPasswd.class.php");
  include_once("./classes/providers/PasswdUserProvider.class.php");
  $userView = \svnadmin\providers\PasswdUserProvider::getInstance();
  $appEngine->setUserViewProvider( $userView );
}
elseif ($cfg->getValue("Engine:Providers", "UserViewProviderType") == "digest")
{
  include_once($ifcorelib_path."IF_HtDigest.class.php");
  include_once( "./classes/providers/DigestUserProvider.class.php" );
  $userView = \svnadmin\providers\DigestUserProvider::getInstance();
  $appEngine->setUserViewProvider( $userView );
}
elseif ($cfg->getValue("Engine:Providers", "UserViewProviderType") == "ldap")
{
	$userView = null;
	include_once("./classes/providers/ldap/LdapUserViewProvider.class.php");
  
	if ($cfg->getValueAsBoolean('Ldap', 'CacheEnabled', false)) {
		include_once("./classes/providers/ldap/CachedLdapUserViewProvider.class.php");
		include_once("./include/ifcorelib/IF_JsonObjectStorage.class.php");
		$userView = \svnadmin\providers\ldap\CachedLdapUserViewProvider::getInstance();
	}
	else {
		$userView = \svnadmin\providers\ldap\LdapUserViewProvider::getInstance();
	}

	$appEngine->setUserViewProvider( $userView );
}

/**
 * User edit provider.
 * LDAP is currently not supported as edit provider.
 */
if ($cfg->getValue("Engine:Providers", "UserEditProviderType") == "passwd")
{
  include_once($ifcorelib_path."IF_HtPasswd.class.php");
  include_once( "./classes/providers/PasswdUserProvider.class.php" );
  $userEdit = \svnadmin\providers\PasswdUserProvider::getInstance();
  $appEngine->setUserEditProvider( $userEdit );
}
if ($cfg->getValue("Engine:Providers", "UserEditProviderType") == "digest")
{
  include_once($ifcorelib_path."IF_HtDigest.class.php");
  include_once( "./classes/providers/DigestUserProvider.class.php" );
  $userEdit = \svnadmin\providers\DigestUserProvider::getInstance();
  $appEngine->setUserEditProvider( $userEdit );
}

/**
 * Group view provider.
 */
if ($cfg->getValue("Engine:Providers", "GroupViewProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $groupView = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setGroupViewProvider( $groupView );
}
elseif($cfg->getValue("Engine:Providers", "GroupViewProviderType") == "ldap" && $cfg->getValue("Engine:Providers", "UserViewProviderType") == "ldap")
{
	$groupView = null;
	include_once("./classes/providers/ldap/LdapUserViewProvider.class.php");
	include_once("./classes/providers/AuthFileGroupAndPathsProvider.class.php");
	
	if ($cfg->getValueAsBoolean('Ldap', 'CacheEnabled', false)) {
		include_once("./classes/providers/ldap/CachedLdapUserViewProvider.class.php");
		include_once("./include/ifcorelib/IF_JsonObjectStorage.class.php");
		$groupView = \svnadmin\providers\ldap\CachedLdapUserViewProvider::getInstance();
	}
	else {
		$groupView = \svnadmin\providers\ldap\LdapUserViewProvider::getInstance();
	}
	
	$appEngine->setGroupViewProvider($groupView);
}

/**
 * Group edit provider.
 * No LDAP support.
 */
if ($cfg->getValue("Engine:Providers", "GroupEditProviderType") == "svnauthfile" )
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $groupEdit = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setGroupEditProvider( $groupEdit );
}

/**
 * Access-Path view provider.
 */
if ($cfg->getValue("Engine:Providers", "AccessPathViewProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $pathsView = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setAccessPathViewProvider( $pathsView );
}

/**
 * Access-Path edit provider.
 */
if ($cfg->getValue("Engine:Providers", "AccessPathEditProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $pathsEdit = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setAccessPathEditProvider( $pathsEdit );
}

/**
 * Repository view provider.
 */
if ($cfg->getValue("Engine:Providers", "RepositoryViewProviderType") == "svnclient")
{
  include_once( "./classes/providers/RepositoryViewProvider.class.php" );
  $repoView = \svnadmin\providers\RepositoryViewProvider::getInstance();
  $appEngine->setRepositoryViewProvider( $repoView );
}

/**
 * Repository edit provider.
 */
if ($cfg->getValue("Engine:Providers", "RepositoryEditProviderType") == "svnclient")
{
  include_once( "./classes/providers/RepositoryEditProvider.class.php" );
  $repoEdit = \svnadmin\providers\RepositoryEditProvider::getInstance();
  $appEngine->setRepositoryEditProvider( $repoEdit );
}

/**
 * Authentication status.
 */
if ($cfg->getValue("Engine:Providers", "AuthenticationStatus") == "basic")
{
  include( "./classes/providers/EngineBaseAuthenticator.class.php" );
  session_start();
  $o = new \svnadmin\providers\EngineBaseAuthenticator;
  $appEngine->setAuthenticator($o);

  // Engine: IAclManager
  // The ACL feature can only be used with authentication.
  if (true)
  {
    include_once("./classes/core/acl/FSAclManager.class.php");
    $o = new \svnadmin\core\acl\FSAclManager($cfg->getValue("ACLManager", "UserRoleAssignmentFile"));
    $appEngine->setAclManager($o);
    $appTemplate->setAcl($appEngine);
  }
}

/**
 * An administrator role MUST be defined, if the authentication is active.
 * Otherwise nobody could setup the application.
 */
$appCurrentScriptFile = currentScriptFileName();
if ($appEngine->isAuthenticationActive() && $appCurrentScriptFile != "settings.php" && $appCurrentScriptFile != "update_ldap.php")
{
  if (!$appEngine->getAclManager()->hasAdminDefined())
  {
    header("Location: settings.php");
    exit(0);
  }
}

///////////////////////////////////////////////////////////////////////////////
// Global User Initinalizations.
///////////////////////////////////////////////////////////////////////////////

//
// Set user locale.
// Check whether the user has choosen a specific language
//

if (isset($_COOKIE["locale"]) && !empty($_COOKIE["locale"]))
{
	// Get locale from user cookie.
	$appTR->setCurrentLocale($_COOKIE["locale"]);
}
else
{
	// Fallback to default locale.
	$appTR->setCurrentLocale(
			$appEngine->getConfig()->getValue("GUI", "DefaultLocale", "en_US")
		);
}

///////////////////////////////////////////////////////////////////////////////
// Global Template Variables.
/////////////////////////////////////////////////////////////////////////////

//
// Locales
//

if (true)
{
	SetValue("LocaleList", $appTR->getAvailableLocales());
}

$appTR->loadModule("global");
?>