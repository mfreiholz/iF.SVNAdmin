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

// 规定当前脚本的错误报告级别,E_ERROR级别是指：运行时致命的错误。不能修复的错误。停止执行脚本。
// 错误日志会写入到日志文件/var/log/httpd/error_log中
// 可以使用error_log($message)或者if_log_debug($message)方式写入日志文件
error_reporting(E_ERROR);
include_once("./classes/util/global.func.php");
// 设置用户自定义的异常处理函数
set_exception_handler('exception_handler');

// Check PHP version.
// 检查PHP版本，最小版本PHP 5.3
if (!checkPHPVersion("5.3")) {
  echo "Wrong PHP version. The minimum required version is: 5.3";
  exit(1);
}

// Does the config.ini file exists?
// 检查配置文件是否存在，data目录是否具备777权限
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
// 核心库
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
// 核心接口和类
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
 * iF.SVNAdmin 的版本信息
 */
define("MAJOR_VERSION", "1");
define("MINOR_VERSION", "6.3");
define("VERSION_EXTRA", "UNOFFICIAL");

/**
 * Constant ACL modules.
 * ACL模块常量
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
 * ACL动作常量
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
define("ACL_ACTION_DOWNLOAD",				"download");			// ACL_MOD_REPO
define("ACL_ACTION_ASSIGN_ADMIN_ROLE", "assignadmin"); // ACL_MOD_ROLE
define("ACL_ACTION_UNASSIGN_ADMIN_ROLE", "unassignadmin"); // ACL_MOD_ROLE

/*
 * Switch current locale procecure.
 * 切换当前本地化处理程序，cookie保存时间一年
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
 * 定义全局变量，其他函数需要调用时，可以使用global $appEngine进行调用
 */
$appEngine = \svnadmin\core\Engine::getInstance(); // app引擎，全局变量

/**
 * Global object to translate strings into different languages.
 * 全局翻译字符串对象
 * @global IF_Translator
 */
$appTR = \IF_Translator::getInstance();
$appTR->setTranslationDirectory($appEngine->getConfig()->getValue("Translation", "Directory"));

/**
 * The template of the current opened page.
 * It supports translations and ACL's.
 * 页面模板对象
 * @global IF_Template
 */
$appTemplate = new \IF_Template;


$cfg = $appEngine->getConfig();

/**
 * User view provider.
 * 使用三种方式提供用户模块，passwd密码形式定义用户，ldap定义用户，digest定义用户
 * 不同用户程序调用不同的PHP文件，产生不同的用户视图对象
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

	$userView->setUserViewEnabled(true);
	$appEngine->setUserViewProvider( $userView );
}

/**
 * User edit provider.
 * LDAP is currently not supported as edit provider.
 * 用户编程提供程序，不支持对LDAP方式的用户进行修改编辑
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
 *
 * 组视图提供程序
 */
// 普通方式通过svnauthfile,也就是SVNAuthFile=/home/svn/authz 控制用户组
if ($cfg->getValue("Engine:Providers", "GroupViewProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $groupView = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setGroupViewProvider( $groupView );
}
// 使用LDAP形式，忽略
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

	$groupView->setGroupViewEnabled(true);
	$appEngine->setGroupViewProvider($groupView);
}

/**
 * Group edit provider.
 * No LDAP support.
 * 组编辑程序，不考虑LDAP
 */
if ($cfg->getValue("Engine:Providers", "GroupEditProviderType") == "svnauthfile" )
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $groupEdit = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setGroupEditProvider( $groupEdit );
}

/**
 * Access-Path view provider.
 * 访问路径视图提供程序
 */
if ($cfg->getValue("Engine:Providers", "AccessPathViewProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $pathsView = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setAccessPathViewProvider( $pathsView );
}

/**
 * Access-Path edit provider.
 * 访问路径编辑提供程序
 */
if ($cfg->getValue("Engine:Providers", "AccessPathEditProviderType") == "svnauthfile")
{
  include_once( "./classes/providers/AuthFileGroupAndPathsProvider.class.php" );
  $pathsEdit = \svnadmin\providers\AuthFileGroupAndPathProvider::getInstance();
  $appEngine->setAccessPathEditProvider( $pathsEdit );
}

/**
 * Repository view provider.
 * 仓库视图提供程序
 */
if ($cfg->getValue("Engine:Providers", "RepositoryViewProviderType") == "svnclient")
{
  include_once( "./classes/providers/RepositoryViewProvider.class.php" );
  $repoView = \svnadmin\providers\RepositoryViewProvider::getInstance();
  $appEngine->setRepositoryViewProvider( $repoView );
}

/**
 * Repository edit provider.
 * 仓库编辑提供程序
 */
if ($cfg->getValue("Engine:Providers", "RepositoryEditProviderType") == "svnclient")
{
  include_once( "./classes/providers/RepositoryEditProvider.class.php" );
  $repoEdit = \svnadmin\providers\RepositoryEditProvider::getInstance();
  $appEngine->setRepositoryEditProvider( $repoEdit );
}

/**
 * Authentication status.
 * 认证状态
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
 * 必须设置一个管理员角色，否则没有人能够设置当前应用
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
            // $appEngine->getConfig()->getValue("GUI", "DefaultLocale", "en_US")
            // 将默认的语言改成中文
			$appEngine->getConfig()->getValue("GUI", "DefaultLocale", "zh_CN")
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