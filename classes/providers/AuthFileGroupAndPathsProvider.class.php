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
namespace svnadmin\providers;

class AuthFileGroupAndPathProvider implements	\svnadmin\core\interfaces\IGroupViewProvider,
												\svnadmin\core\interfaces\IGroupEditProvider,
												\svnadmin\core\interfaces\IPathsViewProvider,
												\svnadmin\core\interfaces\IPathsEditProvider
{
	/**
	 * The singelton instance of this class.
	 * @var \svnadmin\providers\AuthFileGroupAndPathProvider
	 */
	private static $m_instance = NULL;

	/**
	 * @var bool
	 */
	private $m_init_done = false;

	/**
	 * The object to manage the SVNAuthFile.
	 * @var IF_SVNAuthFileC
	 */
	private $m_authfile = null;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Gets the singelton instance of this object.
	 *
	 * @return \svnadmin\providers\AuthFileGroupAndPathProvider
	 */
	public static function getInstance()
	{
		if (self::$m_instance == null)
		{
			self::$m_instance = new AuthFileGroupAndPathProvider();
		}
		return self::$m_instance;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IProvider::init()
	 */
	public function init()
	{
		global $appEngine;
		if( !$this->m_init_done )
		{
			$this->m_init_done = true;
			$this->m_authfile = new \IF_SVNAuthFileC($appEngine->getConfig()->getValue("Subversion", "SVNAuthFile"));
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::reset()
	 */
	public function reset()
	{
		$E = \svnadmin\core\Engine::getInstance();
		$this->m_authfile = new \IF_SVNAuthFileC($E->getConfig()->getValue("Subversion", "SVNAuthFile"));
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IEditProvider::save()
	 */
	public function save()
	{
		return $this->m_authfile->save();
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::isUpdateable()
	 */
	public function isUpdateable()
	{
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::update()
	 */
	public function update()
	{
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroups()
	 */
	public function getGroups()
	{
		$groupNamesArray = $this->m_authfile->groups();
		$retArray = array();
		if( is_array( $groupNamesArray ) )
		{
			for( $i=0; $i<count($groupNamesArray); $i++ )
			{
				$groupObj = new \svnadmin\core\entities\Group;
				$groupObj->id = $groupNamesArray[$i];
				$groupObj->name = $groupNamesArray[$i];
				array_push( $retArray, $groupObj );
			}
		}
		return $retArray;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::groupExists()
	 */
	public function groupExists($objGroup)
	{
		return $this->m_authfile->groupExists($objGroup->name);
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroupsOfUser()
	 */
	public function getGroupsOfUser($objUser)
	{
		$retArray = array();
		$groupNamesArray = $this->m_authfile->groupsOfUser( $objUser->name );

		if( is_array($groupNamesArray) )
		{
			for( $i=0; $i<count($groupNamesArray); $i++ )
			{
				$groupObj = new \svnadmin\core\entities\Group;
				$groupObj->id = $groupNamesArray[$i];
				$groupObj->name = $groupNamesArray[$i];
				array_push( $retArray, $groupObj );
			}
		}
		return $retArray;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getUsersOfGroup()
	 */
	public function getUsersOfGroup($objGroup)
    {
		$retArray = array();
		$userNamesArray = $this->m_authfile->usersOfGroup( $objGroup->name );

		if( is_array($userNamesArray) )
		{
			for( $i=0; $i<count($userNamesArray); $i++ )
			{
				$userObj = new \svnadmin\core\entities\User();
				$userObj->id = $userNamesArray[$i];
				$userObj->name = $userNamesArray[$i];
				array_push( $retArray, $userObj );
			}
		}
		return $retArray;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::isUserInGroup()
	 */
	public function isUserInGroup($objUser, $objGroup)
	{
		return $this->m_authfile->isUserInGroup( $objGroup->name, $objUser->name );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupEditProvider::addGroup()
	 */
	public function addGroup( $objGroup )
	{
		return $this->m_authfile->createGroup($objGroup->name);
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupEditProvider::deleteGroup()
	 */
	public function deleteGroup( $objGroup )
	{
		return $this->m_authfile->deleteGroup($objGroup->name);
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupEditProvider::assignUserToGroup()
	 */
	public function assignUserToGroup( $objUser, $objGroup )
	{
		return $this->m_authfile->addUserToGroup( $objGroup->name, $objUser->name );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupEditProvider::removeUserFromGroup()
	 */
	public function removeUserFromGroup( $objUser, $objGroup )
	{
		return $this->m_authfile->removeUserFromGroup( $objUser->name, $objGroup->name );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupEditProvider::removeUserFromAllGroups()
	 */
	public function removeUserFromAllGroups( $objUser )
	{
		$groups = $this->m_authfile->groupsOfUser($objUser->name);
		for( $i=0; $i<count($groups); $i++ )
		{
			$this->m_authfile->removeUserFromGroup( $objUser->name, $groups[$i] );
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getPaths()
	 */
	public function getPaths()
	{
		$list = array();
		$paths = $this->m_authfile->repositories();
		for( $i=0; $i<count($paths); $i++ )
		{
			$o = new \svnadmin\core\entities\AccessPath;
			$o->path = $paths[$i];
			array_push( $list, $o );
		}
		return $list;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getPathsOfGroup()
	 */
	public function getPathsOfGroup( $objGroup )
	{
		$list = array();
		$perms = $this->m_authfile->permissionsOfGroup( $objGroup->name );
		$permsLen = count($perms);
		for( $i=0; $i<$permsLen; $i++ )
		{
			$oAP = new \svnadmin\core\entities\AccessPath;
			$oAP->path = $perms[$i][0];
			$oAP->perm = self::resolvePermission2($perms[$i][1]);
			array_push( $list, $oAP );
		}
		return $list;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getPathsOfUser()
	 */
	public function getPathsOfUser( $objUser )
	{
		$list = array();
		$perms = $this->m_authfile->permissionsOfUser( $objUser->name );
		$permsLen = count($perms);
		for( $i=0; $i<$permsLen; $i++ )
		{
			$oAP = new \svnadmin\core\entities\AccessPath;
			$oAP->path = $perms[$i][0];
			$oAP->perm = self::resolvePermission2($perms[$i][1]);
			$oAP->inherited = $perms[$i][2];
			array_push( $list, $oAP );
		}
		return $list;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getPathsOfRepository()
	 */
	public function getPathsOfRepository($objRepository)
	{
		$list = array();
		$paths = $this->m_authfile->repositories();

		// Build root access path for repository. e.g.: "my_repo" => "my_repo:/"
		$repo_access_path_root = $objRepository->getName() . ':/';

		for( $i=0; $i<count($paths); $i++ )
		{
			$pos = strpos($paths[$i], $repo_access_path_root);
			if ($pos === 0)
			{
				$list[] = new \svnadmin\core\entities\AccessPath($paths[$i]);
			}
		}

		return $list;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getUsersOfPath()
	 */
	public function getUsersOfPath($objAccessPath)
	{
		$ret = array();

		$users = $this->m_authfile->usersOfRepository($objAccessPath->getPath());
		foreach ($users as $u)
		{
			$perm = $this->m_authfile->permissionsOfUser($u, false, $objAccessPath->getPath());
			$permString = $this->resolvePermission2($perm[0][1]);

			$o = new \svnadmin\core\entities\User();
			$o->id = $u;
			$o->name = $u;
			$o->perm = $permString;
			$ret[] = $o;
		}

		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsViewProvider::getGroupsOfPath()
	 */
	public function getGroupsOfPath( $objAccessPath )
	{
		$ret = array();

		$groups = $this->m_authfile->groupsOfRepository($objAccessPath->getPath());
		foreach ($groups as $g)
		{
			$perm = $this->m_authfile->permissionsOfGroup($g, false, $objAccessPath->getPath());
			$permString = $this->resolvePermission2($perm[0][1]);

			$o = new \svnadmin\core\entities\Group;
			$o->id = $g;
			$o->name = $g;
			$o->perm = $permString;
			$ret[] = $o;
		}

		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::deleteAccessPath()
	 */
	public function deleteAccessPath($objAccessPath)
	{
		return $this->m_authfile->removeRepositoryPath( $objAccessPath->path );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::createAccessPath()
	 */
	public function createAccessPath($objAccessPath)
	{
		return $this->m_authfile->addRepositoryPath( $objAccessPath->path );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::removeGroupFromAllAccessPaths()
	 */
	public function removeGroupFromAllAccessPaths($objGroup)
	{
		$paths = $this->m_authfile->repositoryPathsOfGroup( $objGroup->name );
		for( $i=0; $i<count($paths); $i++ )
		{
			$this->m_authfile->removeGroupFromRepository( $objGroup->name, $paths[$i] );
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::removeUserFromAllAccessPaths()
	 */
	public function removeUserFromAllAccessPaths($objUser)
	{
		$paths = $this->m_authfile->repositoryPathsOfUser( $objUser->name );
		for( $i=0; $i<count($paths); $i++ )
		{
			$this->m_authfile->removeUserFromRepository( $objUser->name, $paths[$i] );
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::removeUserFromAccessPath()
	 */
	public function removeUserFromAccessPath($objUser, $objAccessPath)
	{
		return $this->m_authfile->removeUserFromRepository( $objUser->name, $objAccessPath->path );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::removeGroupFromAccessPath()
	 */
	public function removeGroupFromAccessPath($objGroup, $objAccessPath)
	{
		return $this->m_authfile->removeGroupFromRepository( $objGroup->name, $objAccessPath->path );
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::assignGroupToAccessPath()
	 */
	public function assignGroupToAccessPath($objGroup, $objAccessPath, $objPermission)
	{
		$p = self::resolvePermission( $objPermission );
		if( $p !== FALSE )
		{
			return $this->m_authfile->addGroupToRepository( $objGroup->name, $objAccessPath->path, $p );
		}
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IPathsEditProvider::assignUserToAccessPath()
	 */
	public function assignUserToAccessPath($objUser, $objAccessPath, $objPermission)
	{
		$p = self::resolvePermission( $objPermission );
		if( $p !== FALSE )
		{
			return $this->m_authfile->addUserToRepository( $objUser->name, $objAccessPath->path, $p );
		}
		return false;
	}

	/**
	 *
	 * @param \svnadmin\core\entities\Permission $objPermission
	 *
	 * @return string
	 */
	private function resolvePermission( $objPermission )
	{
		$p = $objPermission->getPerm();
		if( $p == \svnadmin\core\entities\Permission::$PERM_NONE )
		{
			return \IF_SVNAuthFileC::$PERMISSION_NONE;
		}
		else if( $p == \svnadmin\core\entities\Permission::$PERM_READ )
		{
			return \IF_SVNAuthFileC::$PERMISSION_READ;
		}
		else if( $p == \svnadmin\core\entities\Permission::$PERM_READWRITE )
		{
			return \IF_SVNAuthFileC::$PERMISSION_READWRITE;
		}
		return \IF_SVNAuthFileC::$PERMISSION_NONE;
	}

	/**
	 *
	 * @param string $strPerm
	 *
	 * @return \svnadmin\core\entities\Permission
	 */
	private function resolvePermission2( $strPerm )
	{
		if( $strPerm == \IF_SVNAuthFileC::$PERMISSION_NONE )
		{
			return \svnadmin\core\entities\Permission::$PERM_NONE;
		}
		else if( $strPerm == \IF_SVNAuthFileC::$PERMISSION_READ )
		{
			return \svnadmin\core\entities\Permission::$PERM_READ;
		}
		else if( $strPerm == \IF_SVNAuthFileC::$PERMISSION_READWRITE )
		{
			return \svnadmin\core\entities\Permission::$PERM_READWRITE;
		}
		return \svnadmin\core\entities\Permission::$PERM_NONE;
	}
}
?>