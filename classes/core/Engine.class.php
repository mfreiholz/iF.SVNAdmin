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
namespace svnadmin\core;

///////////////////////////////////////////////////////////////////////////////
// Global defines which are required for Engine usage.
///////////////////////////////////////////////////////////////////////////////

/**
 * Constant provider identifiers.
 */
define("PROVIDER_USER_VIEW",           1);
define("PROVIDER_USER_EDIT",           2);
define("PROVIDER_GROUP_VIEW",          3);
define("PROVIDER_GROUP_EDIT",          4);
define("PROVIDER_ACCESSPATH_VIEW",     5);
define("PROVIDER_ACCESSPATH_EDIT",     6);
define("PROVIDER_REPOSITORY_VIEW",     7);
define("PROVIDER_REPOSITORY_EDIT",     8);
define("PROVIDER_AUTHENTICATION",      9);

/**
 * Page forward defines.
 */
define("PAGE_HOME",				"index.php");
define("PAGE_LOGIN",			"login.php");
define("PAGE_ERROR",			"error.php");

/**
 * Error type defines.
 */
define("ERROR_INVALID_MODULE",		"invalid_module");
define("ERROR_NO_ACCESS",			"no_access");

///////////////////////////////////////////////////////////////////////////////
// Class: Engine
///////////////////////////////////////////////////////////////////////////////

class Engine
{
	/**
	 * Holds the singelton instance of this class.
	 * @var \svnadmin\core\Engine
	 */
	static private $m_instance = null;

	/**
	 * Holds the global configuration.
	 * @var \IF_IniFile
	 */
	private $m_config = null;

	/**
	 * @var \svnadmin\core\interfaces\IUserViewProvider
	 */
	private $m_userViewProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IUserEditProvider
	 */
	private $m_userEditProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IGroupViewProvider
	 */
	private $m_groupViewProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IGroupEditProvider
	 */
	private $m_groupEditProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IPathsViewProvider
	 */
	private $m_pathViewProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IPathsEditProvider
	 */
	private $m_pathEditProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IRepositoryViewProvider
	 */
	private $m_repositoryViewProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IRepositoryEditProvider
	 */
	private $m_repositoryEditProvider = null;

	/**
	 * @var \svnadmin\core\interfaces\IAuthenticator
	 */
	private $m_authenticator = null;

	/**
	 * @var \svnadmin\core\interfaces\IAclManager
	 */
	private $m_acl_manager = null;

	/**
	 * Catched application exceptions. They should be shown to the user.
	 * @var array<Exception>
	 */
	private $m_exceptions = array();

	/**
	 * Holds global application messages. They should be shown to the user.
	 * @var array<string>
	 */
	private $m_messages = array();

	/**
	 * Private constructor, because the class is a singelton instance.
	 * @return \svnadmin\core\Engine
	 */
	private function __construct()
	{
		// Load the global configuration.
		$this->m_config = new \IF_IniFile();
		$this->m_config->loadFromFile("./data/config.ini");
	}

	/**
	 * Gets the singelton instance of this class.
	 *
	 * @return \svnadmin\core\Engine
	 */
	public static function getInstance()
	{
		if (self::$m_instance == null)
		{
			self::$m_instance = new Engine;
		}
		return self::$m_instance;
	}

	/**
	 * Gets the current software version as compelete string.
	 *
	 * @return string
	 */
	public function getAppVersionString()
	{
		return \MAJOR_VERSION.".".\MINOR_VERSION." ".\VERSION_EXTRA;
	}

	/**
	 * Adds the exception to the internal list of occured exceptions.
	 *
	 * @param \Exception $ex
	 */
	public function addException(\Exception $ex)
	{
		$this->m_exceptions[] = $ex;
	}

	/**
	 * Gets a list with occured exceptions.
	 *
	 * @return array<Exception>
	 */
	public function getExceptions()
	{
		return $this->m_exceptions;
	}

	/**
	 * Adds a global application message to the internal list.
	 *
	 * @param string
	 */
	public function addMessage($message)
	{
		$this->m_messages[] = $message;
	}

	/**
	 * Gets all defined global application messages.
	 *
	 * @return array<string>
	 */
	public function getMessages()
	{
		return $this->m_messages;
	}

	/**
	 * Gets the global configuration object.
	 *
	 * @return \IF_IniFile
	 */
	public function getConfig()
	{
		return $this->m_config;
	}

	/**
	 * Indicates, whether one of the views is updateable.
	 *
	 * @return bool
	 */
	public function isViewUpdateable()
	{
		if ($this->isProviderActive(PROVIDER_USER_VIEW) && $this->m_userViewProvider->isUpdateable())
			return true;

		if ($this->isProviderActive(PROVIDER_GROUP_VIEW) && $this->m_groupViewProvider->isUpdateable())
			return true;

		if ($this->isProviderActive(PROVIDER_ACCESSPATH_VIEW) && $this->m_pathViewProvider->isUpdateable())
			return true;

		if ($this->isProviderActive(PROVIDER_REPOSITORY_VIEW) && $this->m_repositoryViewProvider->isUpdateable())
			return true;

		return false;
	}

	/**
	 * Forwards the user to a specific page.
	 *
	 * @param string $location Destination URL or relative path.
	 * @param array $args Custom arguments for the forward.
	 * @param bool $immediate Wheather the forward should be handled immediatly by calling "exit(0)"
	 * @param int $returnCode The return code of "exit()" call. Based on $immediate parameter.
	 */
	public function forward($location, array $args=null, $immediate = false, $returnCode = 0)
	{
		// Handle custom parameters.
		$params = "";
		if ($args != null && count($args) > 0)
		{
			$i = 0;
			foreach ($args as $key => $value)
			{
				$params.= ($i == 0 ? "?" : "&");
				$params.= $key . "=" . $value;
				++$i;
			}
		}

		// Set the forward now.
		header('Location: ' . $location . $params);

		// Do it NOW or let the sciprt finish execution?
		if ($immediate)
		{
			exit($returnCode);
		}
	}

	/**
	 * Forwards the user to the error page due to a specific error.
	 *
	 * @param string $errorType
	 * @param array $args Parameters for the forward
	 */
	public function forwardError($errorType, array $args=null)
	{
		if ($args == null)
		{
			$args = array();
		}
		$args["e"] = $errorType;
		$this->forward(PAGE_ERROR, $args, true, 0);
	}

	/**
	 * Loads the given action file and handles it.
	 *
	 * @param string $action The name of the action
	 * @return void
	 */
	public function handleAction( $action )
	{
		global $appEngine;
		global $appTemplate;	// @todo Remove this variable from this place.
		global $appTR;			// @todo Remove this variable from this place.

		$appTR->loadModule("actions");

		if(!defined('ACTION_HANDLING'))
		{
			define('ACTION_HANDLING', true);
		}

		// Path to action implementation.
		$filename = 'actions/'.$action.'.php';

		if(file_exists($filename))
		{
			$code = file_get_contents($filename);
			eval(' ?>'.$code.'<?php ');
		}
		else
		{
			throw new Exception('Can not find implementation for action: '.$action);
		}
	}

	/**
	 * This function checks whether the current user is authenticated and
	 * has permission to access a specific resource.
	 *
	 * Note: This function should take place on every page, where an authentication is required.
	 *
	 * @param bool $redirect Indicates whether the user should be redirected to login page.
	 * @param string $module Name of the module.
	 * @param string $action
	 */
	public function checkUserAuthentication($redirect=true, $module=null, $action=null)
	{
		if(!$this->isAuthenticationActive())
		{
			// The authentication is turned off.
			return true;
		}

		// At this place the authentication is ON.
		if (!isset($_SESSION["svnadmin_username"]) || empty($_SESSION["svnadmin_username"]))
		{
			// The user is not logged in.
			if ($redirect)
			{
				$this->forward(PAGE_LOGIN, null, true);
			}
			return false;
		}

		// Check acl permissions.
		if ($this->m_acl_manager !== null && $module !== null && $action !== null)
		{
			$b = $this->checkUserAccess($module, $action);
			if (!$b)
			{
				// No permission.
				if ($redirect)
				{
					$this->forwardError(ERROR_NO_ACCESS, array("m" => $module, "a" => $action));
				}
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks whether the current logged in user has access to a
	 * specific resource.
	 *
	 * @param string $module
	 * @param string $action
	 * @return bool
	 */
	public function checkUserAccess($module, $action)
	{
		if (isset($_SESSION["svnadmin_username"]))
		{
			$u = new \svnadmin\core\entities\User();
			$u->name = $_SESSION["svnadmin_username"];

			return $this->getAclManager()->hasPermission($u, $module, $action);
		}
		return false;
	}

	/**
	 * Same as {@link \svnadmin\core\Engine::checkUserAccess}
	 *
	 * @see checkUserAccess
	 */
	public function hasPermission($module, $action)
	{
		return $this->checkUserAccess($module, $action);
	}

	/**
	 * Gets the name of the current logged in user.
	 *
	 * @return string
	 */
	public function getSessionUsername()
	{
		if (isset($_SESSION["svnadmin_username"]) && !empty($_SESSION["svnadmin_username"]))
		{
			return $_SESSION["svnadmin_username"];
		}
		return null;
	}

	/**
	 * Indicates whether the provider implementation with the given module-id
	 * is active and ready for usage.
	 *
	 * @param int $providerId The provider-ID
	 * @return bool
	 */
	public function isProviderActive($providerId)
	{
		switch ($providerId)
		{
			case PROVIDER_USER_VIEW:
				return $this->m_userViewProvider == null ? false : true;
			case PROVIDER_USER_EDIT:
				return $this->m_userEditProvider == null ? false : true;

			case PROVIDER_GROUP_VIEW:
				return $this->m_groupViewProvider == null ? false : true;
			case PROVIDER_GROUP_EDIT:
				return $this->m_groupEditProvider == null ? false : true;

			case PROVIDER_ACCESSPATH_VIEW:
				return $this->m_pathViewProvider == null ? false : true;
			case PROVIDER_ACCESSPATH_EDIT:
				return $this->m_pathEditProvider == null ? false : true;

			case PROVIDER_REPOSITORY_VIEW:
				return $this->m_repositoryViewProvider == null ? false : true;
			case PROVIDER_REPOSITORY_EDIT:
				return $this->m_repositoryEditProvider == null ? false : true;

			case PROVIDER_AUTHENTICATION:
				return $this->m_authenticator == null ? false : true;

			default:
				if_log_debug('Unknown Provider ID: '.$providerId);
				return false;
		}
		return false;
	}

	/**
	 * Gets the provider which matches the specific provider-id.
	 *
	 * @param int $providerId
	 *
	 * @return \svnadmin\core\interfaces\IProvider
	 */
	public function getProvider($providerId)
	{
		$prov = null;
		switch ($providerId)
		{
			case PROVIDER_USER_VIEW:
				$prov = $this->m_userViewProvider;
				break;

			case PROVIDER_USER_EDIT:
				$prov = $this->m_userEditProvider;
				break;

			case PROVIDER_GROUP_VIEW:
				$prov = $this->m_groupViewProvider;
				break;

			case PROVIDER_GROUP_EDIT:
				$prov = $this->m_groupEditProvider;
				break;

			case PROVIDER_ACCESSPATH_VIEW:
				$prov = $this->m_pathViewProvider;
				break;

			case PROVIDER_ACCESSPATH_EDIT:
				$prov = $this->m_pathEditProvider;
				break;

			case PROVIDER_REPOSITORY_VIEW:
				$prov = $this->m_repositoryViewProvider;
				break;

			case PROVIDER_REPOSITORY_EDIT:
				$prov = $this->m_repositoryEditProvider;
				break;

			case PROVIDER_AUTHENTICATION:
				$prov = $this->m_authenticator;
				break;

			default:
				if_log_debug("Unknown Provider ID: ".$providerId);
				return null;
		}

		if ($prov != null) {
			$prov->init();
		}
		return $prov;
	}


	/**
	 * Checks whether the authentication module is active.
	 *
	 * @return bool
	 */
	public function isAuthenticationActive()
	{
		return $this->m_authenticator == null ? false : true;
	}

	/**
	 * Sets the user authenticator for the application.
	 *
	 * @param \svnadmin\core\interfaces\IAuthenticator $objAuthenticator The authenticator implementation class.
	 */
	public function setAuthenticator(\svnadmin\core\interfaces\IAuthenticator $objAuthenticator)
	{
		$this->m_authenticator = $objAuthenticator;
	}

	/**
	 * Gets the user authenticator for the application
	 *
	 * @return \svnadmin\core\interfaces\IAuthenticator
	 */
	public function getAuthenticator()
	{
		if ($this->m_authenticator != null)
			$this->m_authenticator->init();
		return $this->m_authenticator;
	}

	/**
	 * Checks whether the IAclManager implementation is active.
	 *
	 * @return bool
	 */
	public function isAclManagerActive()
	{
		if ($this->isAuthenticationActive())
			if ($this->m_acl_manager != null)
				return true;
		return false;
	}

	/**
	 * Sets the ACL manager for the application.
	 *
	 * @param \svnadmin\core\interfaces\IAclManager $aclManager The ACL manager instance.
	 */
	public function setAclManager(\svnadmin\core\interfaces\IAclManager $aclManager)
	{
		$this->m_acl_manager = $aclManager;
	}

	/**
	 * Gets the ACL manager of the application.
	 *
	 * @return \svnadmin\core\interfaces\IAclManager
	 */
	public function getAclManager()
	{
		if ($this->m_acl_manager != null)
			$this->m_acl_manager->init();
		return $this->m_acl_manager;
	}

  /**
   * Checks whether the user-view is active.
   * @return bool
   */
  public function isUserViewActive()
  {
    return $this->m_userViewProvider == null ? false : true;
  }

  /**
   * Sets the user view provider.
   * @param IUserViewProvider
   */
  public function setUserViewProvider( $o )
  {
    $this->m_userViewProvider = $o;
  }

  /**
   * Gets the user view provider.
   * @return IUserViewProvider or null
   */
  public function getUserViewProvider()
  {
    if( $this->m_userViewProvider != null )
    {
      $this->m_userViewProvider->init();
    }
    return $this->m_userViewProvider;
  }

  /**
   * Checks whether the user-edit is active.
   * @return bool
   */
  public function isUserEditActive()
  {
    return $this->m_userEditProvider == null ? false : true;
  }

  /**
   * Sets the user edit provider.
   * @param IUserEditProvider
   */
  public function setUserEditProvider( $o )
  {
    $this->m_userEditProvider = $o;
  }

  /**
   * Gets the user edit provider.
   * @return IUserEditProvider
   */
  public function getUserEditProvider()
  {
    if( $this->m_userEditProvider != null )
    {
      $this->m_userEditProvider->init();
    }
    return $this->m_userEditProvider;
  }

  public function isGroupViewActive()
  {
    return $this->m_groupViewProvider == null ? false : true;
  }

  public function setGroupViewProvider( $o )
  {
    $this->m_groupViewProvider = $o;
  }

  public function getGroupViewProvider()
  {
    if( $this->m_groupViewProvider != null )
    {
      $this->m_groupViewProvider->init();
    }
    return $this->m_groupViewProvider;
  }

  public function isGroupEditActive()
  {
    return $this->m_groupEditProvider == null ? false : true;
  }

  public function setGroupEditProvider( $o )
  {
    $this->m_groupEditProvider = $o;
  }

  public function getGroupEditProvider()
  {
    if( $this->m_groupEditProvider != null )
    {
      $this->m_groupEditProvider->init();
    }
    return $this->m_groupEditProvider;
  }

  /////

  public function isAccessPathViewActive()
  {
    return $this->m_pathViewProvider == null ? false : true;
  }

  public function setAccessPathViewProvider( $o )
  {
    $this->m_pathViewProvider = $o;
  }

  public function getAccessPathViewProvider()
  {
    if( $this->m_pathViewProvider != null )
    {
      $this->m_pathViewProvider->init();
    }
    return $this->m_pathViewProvider;
  }

  public function isAccessPathEditActive()
  {
    return $this->m_pathEditProvider == null ? false : true;
  }

  public function setAccessPathEditProvider( $o )
  {
    $this->m_pathEditProvider = $o;
  }

  public function getAccessPathEditProvider()
  {
    if( $this->m_pathEditProvider != null )
    {
      $this->m_pathEditProvider->init();
    }
    return $this->m_pathEditProvider;
  }

  /////

  public function isRepositoryViewActive()
  {
    return $this->m_repositoryViewProvider == null ? false : true;
  }

  public function setRepositoryViewProvider( $o )
  {
    $this->m_repositoryViewProvider = $o;
  }

  public function getRepositoryViewProvider()
  {
    if( $this->m_repositoryViewProvider != null )
    {
      $this->m_repositoryViewProvider->init();
    }
    return $this->m_repositoryViewProvider;
  }

  /////

  public function isRepositoryEditActive()
  {
    return $this->m_repositoryEditProvider == null ? false : true;
  }

  public function setRepositoryEditProvider( $o )
  {
    $this->m_repositoryEditProvider = $o;
  }

  public function getRepositoryEditProvider()
  {
    if( $this->m_repositoryEditProvider != null )
    {
      $this->m_repositoryEditProvider->init();
    }
    return $this->m_repositoryEditProvider;
  }


	////////////////////////////////////////////////////////////////////
	// Deprecated methods.
	////////////////////////////////////////////////////////////////////

	/**
	 * Forwards the user to the "Inactive/invalid module" error page.
	 *
	 * @param bool $forward Indicates whether the forward should be handled. (default=true)
	 * @deprecated use {@link \svnadmin\core\Engine::forward()} instead.
	 */
	public function forwardInvalidModule($forward = true)
	{
		if($forward)
		{
			$this->forward("error.php?e=inactive_module");
		}
	}

	/**
	 * Deletes the group and removes all associations of this group.
	 *
	 * @param Group $group
	 * @return bool
	 *
	 * @deprecated
	 */
	public function deleteGroup($group)
	{
		if( $this->getAccessPathEditProvider()->removeGroupFromAllAccessPaths($group) )
		{
			if( $this->getGroupEditProvider()->deleteGroup($group) )
			{
				$this->getAccessPathEditProvider()->save();
				$this->getGroupEditProvider()->save();
				return true;
			}
		}
		return false;
	}
}
?>