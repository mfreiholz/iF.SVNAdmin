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
/**
 * This class provides functionality to manage the SVNAuthFile.
 * Managing group and user rights for repositories.
 *
 * Issue list:
 * - Add user alias support.
 * - Add support for groups as member of other groups.
 *
 * @author Manuel Freiholz (Gainwar)
 * @since 08/02/2009
 * @copyright insaneFactory.com
 * @require inifile.func.php
 */
class IF_SVNAuthFileC
{
	public static $PERMISSION_NONE		= '';  // 静态属性， 无权限标志
	public static $PERMISSION_READ		= 'r'; // 静态属性，读权限标志
	public static $PERMISSION_READWRITE	= 'rw'; // 静态属性，读写权限标志

	private $SIGN_ALL_USERS	= '*'; // 所有用户标志
	private $GROUP_SIGN		= '@'; // 组标志
	private $GROUP_SECTION	= 'groups'; // 组section标志
	private $ALIAS_SIGN		= '&'; // 假名标志
	private $ALIAS_SECTION	= 'aliases'; // 假名section标志

	/**
	 * Holds the IF_Config object which is used to manage
	 * all actions on SVNAuthFile (INI-format).
	 *
	 * @var IF_Config
     * $config用于管理所有的配置文件中的对象
     *
	 */
	private $config = null;

	/**
	 * Constructor
	 *
	 * @param string $path Path to the SVNAuthFile
	 *
	 * @throws Exception
	 */
	public function __construct($path = null)
	{
		if (!empty($path))
		{
			self::open($path);
		}
	}

	/**
	 * Open the given SVNAuthFile, which contains permissions
	 * of the svn users/groups.
	 *
	 * @param string $path Path to the SVNAuthFile
	 *
	 * @return bool
	 *
	 * @throws Exception
     *
     * 打开配置文件
	 */
	public function open($path)
	{
		try {
			$this->config = new IF_Config($path);
			return true;
		}
		catch (Exception $e) {
			throw new Exception("Can not read SVNAuthFile.", 0, $e);
		}
	}

	/**
	 * Writes the changed SVNAuthFile to the given destination file.
	 * If $path is 'null' then it will be written to the same file from
	 * which the data has been read.
	 *
	 * @param string $path
	 *
	 * @return bool
	 *
	 * @throws Exception
     *
     * 保存配置文件
	 */
	public function save($path = null)
	{
		try {
		    // 写数据到config.ini配置文件中
            if_log_debug('IF_SVNAuthFileC:写数据到config.ini配置文件中');
			return $this->config->save($path);
		}
		catch (Exception $e) {
			throw new Exception("Can not write SVNAuthFile.", 0, $e);
		}
	}

	/**
	 * Gets all existing aliases.
	 *
	 * @return array <string>
     *
     * 获取所的的假名
	 */
	public function aliases()
	{
		return $this->config->getSectionKeys($this->ALIAS_SECTION);
	}

	/**
	 * Gets all existing groups.
	 *
	 * @return array <string>
     *
     * 获取所的的组
	 */
	public function groups()
	{
		return $this->config->getSectionKeys($this->GROUP_SECTION);
	}

	/**
	 * Gets all configured repositories + repository-path
	 *
	 * @return array<string>
     *
     * 获取所有的仓库和仓库路径
	 */
	public function repositories()
	{
		$arrSections = $this->config->getSections();
		$ret = array();

		foreach ($arrSections as $section)
		{
			if ($section != $this->GROUP_SECTION && $section != $this->ALIAS_SECTION && !empty($section)) // empty = keys without section header.
			{
				$ret[] = $section;
			}
		}

		return $ret;
	}

	/**
	 * Resolves the given alias to its real value.
	 *
	 * @param string $alias
	 *
	 * @return string
     * 获取假名的真值
	 */
	public function getAliasValue($alias)
	{
		$aliasKey = $alias;
		if (strpos($aliasKey, "&") !== 0) {
			$aliasKey = substr($aliasKey, 1);
		}
		return $this->config->getValue($this->ALIAS_SECTION, $aliasKey, $alias);
	}

	/**
	 * Gets all users of the given group.
	 *
	 * @param string $group
	 *
	 * @return array<string>
     * 获取组中的用户
	 */
	public function usersOfGroup($group)
	{
		$usersString = $this->config->getValue($this->GROUP_SECTION, $group);

		if ($usersString != null)
		{
			$arrUsers = explode(',', $usersString);
			$arrUsersLen = count($arrUsers);

			for ($i = 0; $i < $arrUsersLen; ++$i)
			{
				if (strpos($arrUsers[$i], '@') === false)
					$arrUsers[$i] = trim($arrUsers[$i]);
				else
					unset($arrUsers[$i]);
			}

			$arrUsers = array_values($arrUsers);

			return $arrUsers;
		}

		return array();
	}

	/**
	 * Gets all subgroups of the given group.
	 *
	 * @param string $group
	 *
	 * @return array<string>
     *
     * 获取组中的子组
	 */
	public function groupsOfGroup($group)
	{
		$groupString = $this->config->getValue($this->GROUP_SECTION, $group);

		if ($groupString != null)
		{
			$arrGroups = explode(',', $groupString);
			$arrGroupsLen = count($arrGroups);

			for ($i = 0; $i < $arrGroupsLen; ++$i)
			{
				if(strpos($arrGroups[$i], '@') !== false)
					$arrGroups[$i] = str_replace('@', '', trim($arrGroups[$i]));
				else
					unset($arrGroups[$i]);
			}

			$arrGroups = array_values($arrGroups);

			return $arrGroups;
		}

		return array();
	}

	/**
	 * Gets all assigned members and groups which are directly assigned
	 * to the given repository path.
	 *
	 * Groups are indicated with a leading '@' sign.
	 *
	 * @param string $repository
	 *
	 * @return array<string>
     *
     * 获取仓库中被分配的用户和组信息
	 */
	public function membersOfRepository($repository)
	{
		return $this->config->getSectionKeys($repository);
	}

	/**
	 * Gets all users which have direct rights to this repository path.
	 *
	 * @param string $repository
	 *
	 * @return array<string>
     *
     * 获取具备当前仓库路径直接权限所有用户
	 */
	public function usersOfRepository($repository)
	{
		$members = self::membersOfRepository($repository);
		$users = array();

		for ($i = 0; $i < count($members); ++$i)
		{
			if (strpos($members[$i], $this->GROUP_SIGN) === 0)
			{
				// Current members referes to a group.
				// Skip it.
				continue;
			}
			else
			{
				$users[] = $members[$i];
			}
		}

		return $users;
	}

	/**
	 * Gets all groups which have direct rights to this repository path.
	 *
	 * @param string $repository
	 *
	 * @return array<string>
     *
     * 获取具备当前仓库路径直接权限所有组
	 */
	public function groupsOfRepository($repository)
	{
		$members = self::membersOfRepository($repository);
		$groups = array();

		for ($i = 0; $i < count($members); ++$i)
		{
			if (strpos($members[$i], $this->GROUP_SIGN) === 0)
			{
				// Remove the leading '@'-sign before adding group
				// to returning array.
				$groups[] = substr($members[$i], 1);
			}
			else
			{
				// Current member refers to a user.
				// Skip it.
				continue;
			}
		}

		return $groups;
	}

	/**
	 * Gets all groups of which the user is a member.
	 *
	 * @param string $username
	 *
	 * @return array<string>
     *
     *  获取当前用户所在的组列表信息
	 */
	public function groupsOfUser($username)
	{
		$ret = array();

		$groups = self::groups();
		foreach ($groups as $g)
		{
			$users = self::usersOfGroup($g);
			if (in_array($username, $users))
			{
				$ret[] = $g;
			}
		}

		return $ret;
	}

	/**
	 * Gets all groups of which the group is a member.
	 *
	 * @param string $groupname
	 *
	 * @return array<string>
     *
     * 获取当前组所在的组的列表信息
	 */
	public function groupsOfSubgroup($groupname)
	{
		$ret = array();

		$groups = self::groups();
		foreach ($groups as $g)
		{
			$subgroups = self::groupsOfGroup($g);
			if (in_array($groupname, $subgroups))
			{
				$ret[] = $g;
			}
		}

		return $ret;
	}

	/**
	 * Gets all repository paths which got a specific group as member.
	 *
	 * @param string $groupname
	 *
	 * @return array<string>
     *
     * 获取当前组具有的所有仓库路径
	 */
	public function repositoryPathsOfGroup($groupname)
	{
		$ret = array();

		$repositories = $this->repositories();
		foreach ($repositories as $repository)
		{
			$groups = $this->groupsOfRepository($repository);
			if (in_array($groupname, $groups))
			{
				$ret[] = $repository;
			}
		}

		return $ret;
	}

	/**
	 * Gets all repository paths which got a specific user as member.
	 *
	 * @param string $username
	 *
	 * @return array<string>
     *
     * 获取用户有权限的所有仓库路径
	 */
	public function repositoryPathsOfUser($username)
	{
		$ret = array();

		$repositories = $this->repositories();
		foreach ($repositories as $repository)
		{
			$users = $this->usersOfRepository($repository);
			if (in_array($username, $users))
			{
				$ret[] = $repository;
			}
		}

		return $ret;
	}

	/**
	 * Checks whether the repository path already exists in the configuration.
	 *
	 * @param string $repository the repository path
	 *
	 * @return bool
     *
     * 检查仓库路径在配置文件中是否存在
	 */
	public function repositoryPathExists($repository)
	{
		return $this->config->getSectionExists($repository);
	}

	/**
	 * Adds a new repostory configuration path to the SVNAuthFile.
	 *
	 * @param string $repopath
	 * @param string $repodesc
	 *
	 * @return bool true=OK; false=Repository path already exists.
	 *
	 * @throws Exception If an invalid repository path has been provided.
     *
     * 添加一个新的仓库配置路径到SVNAuthFile配置文件中，此处增加了一个参数$repodesc，代表仓库的描述信息
	 */
	public function addRepositoryPath($repopath, $repodesc=null)
	{
    	if (self::repositoryPathExists($repopath))
		{
			// Already exists. 路径已经存在
			return false;
		}

		// Validate the $repopath string.
        // 校验仓库路径有效性
		$pattern = '/^[A-Za-z0-9\_\-.]+:\/.*$/i';
		if ($repopath != "/" && !preg_match($pattern, $repopath))
		{
			throw new Exception('Invalid repository name. (Pattern: '.$pattern.')');
		}

		// Create the repository configuration path.
        // 创建仓库配置路径
		$this->config->setValue($repopath, null, null);

		// 如果用户输入了仓库或访问路径的描述信息，那么就把描述信息写到配置文件中
        // 此处调用setValue方法只是在列表中加入数据，定义详细见include/ifcorelib/IF_Config.class.php文件
		if (!empty($repodesc)) {
		    if_log_debug('将描述信息添加到列表中，描述信息:' . $repodesc . '  仓库路径:' . $repopath);
            $this->config->setValue($repopath, '#section_desc', $repodesc);
        }
		return true;
	}

	/**
	 * Removes the access path from the configuration.
	 *
	 * @param string $repopath
	 *
	 * @return bool
     *
     * 从配置文件中移除访问路径
	 */
	public function removeRepositoryPath($repopath)
	{
		if (!self::repositoryPathExists($repopath))
		{
			return false;
		}

		return $this->config->removeValue($repopath, null);
	}

	/**
	 * Checks whether the group "$groupname" already exists.
	 *
	 * @param string $groupname
	 *
	 * @return bool
     *
     * 检查组是否存在
	 */
	public function groupExists($groupname)
	{
		return $this->config->getValueExists($this->GROUP_SECTION, $groupname);
	}

	/**
	 * Creates the new group "$groupname", if it does not exist.
	 *
	 * @param string $groupname
	 *
	 * @return bool TRUE/FALSE
	 *
	 * @throws Exception If an invalid group name has been provided.
     *
     * 创建组
	 */
	public function createGroup($groupname)
	{
		// Validate the groupname.
		$pattern = '/^[A-Za-z0-9\-\_]+$/i';
		if (!preg_match($pattern, $groupname))
		{
			throw new Exception('Invalid group name "' . $groupname .
					'". Allowed signs are: A-Z, a-z, Underscore, Dash, (no spaces!) ');
		}

		if (self::groupExists($groupname))
		{
			// The group already exists.
			return false;
		}

		$this->config->setValue($this->GROUP_SECTION, $groupname, "");
		return true;
	}

	/**
	 * Deletes the given group by name.
	 *
	 * @param $groupname
	 *
	 * @return bool
     *
     * 删除组
	 */
	public function deleteGroup($groupname)
	{
		if (!self::groupExists($groupname))
		{
			return false;
		}
		return $this->config->removeValue($this->GROUP_SECTION, $groupname);
	}

	/**
	 * Adds the user to group.
	 *
	 * @param string $groupname
	 * @param string $username
	 *
	 * @return bool
     *
     * 给组中添加用户
	 */
	public function addUserToGroup($groupname, $username)
	{
		if (!self::groupExists($groupname))
		{
			return false;
		}

		// Get current users and groups.
		$users = $this->usersOfGroup($groupname);
		$groups = $this->groupsOfGroup($groupname);

		if (!is_array($users) || !is_array($groups))
		{
			return false;
		}

		// NOTE: Its no longer an error when the user is already in group!!!
		// Check whether the user is already in group.
		if (in_array($username, $users))
		{
			return true;
		}

		// Add user to $users array.
		$users[] = $username;

		// Set changes to config.
		$userString = self::convertGroupsUsersToString($groups, $users);
		$this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
		return true;
	}

	/**
	 * Adds the subgroup to group.
	 *
	 * @param string $groupname
	 * @param string $subgroupname
	 *
	 * @return bool
     *
     * 添加子组到组中
	 */
	public function addSubgroupToGroup($groupname, $subgroupname)
	{
		if (!self::groupExists($groupname) || !self::groupExists($subgroupname))
		{
			return false;
		}

		// Get current users and groups.
		$users = $this->usersOfGroup($groupname);
		$groups = $this->groupsOfGroup($groupname);

		if (!is_array($users) || !is_array($groups))
		{
			return false;
		}

		// NOTE: Its no longer an error when the subgroup is already in group!!!
		// Check whether the subgroup is already in group.
		if (in_array($subgroupname, $groups))
		{
			return true;
		}

		// Add subgroup to groups array.
		$groups[] = $subgroupname;

		// Set changes to config.
		$userString = self::convertGroupsUsersToString($groups, $users);
		$this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
 		return true;
	}

	/**
	 * Checks whether the user is in the given group.
	 *
	 * @param string $groupname
	 * @param string $username
	 *
	 * @return bool
     *
     * 检查用户是否在组中
	 */
	public function isUserInGroup($groupname, $username)
	{
		$users = $this->usersOfGroup($groupname);

		if (in_array($username, $users))
		{
			return true;
		}
		return false;
	}

	/**
	 * Checks whether the subgroups is in the given group.
	 *
	 * @param string $groupname
	 * @param string $subgroupname
	 *
	 * @return bool
     *
     * 检查子组是否在组中
	 */
	public function isSubgroupInGroup($groupname, $subgroupname)
	{
		$groups = $this->groupsOfGroup($groupname);

		if (in_array($subgroupname, $groups))
		{
			return true;
		}
		return false;
	}

	/**
	 * Removes the given user from group.
	 *
	 * @param string $username
	 * @param string $groupname
	 *
	 * @return bool
     *
     * 从组中移除用户
	 */
	public function removeUserFromGroup($username, $groupname)
	{
		$groupUsers = $this->usersOfGroup($groupname);

		// Search the user in array.
		$pos = array_search($username, $groupUsers);

		if ($pos !== FALSE)
		{
			// Remove the user from array.
			unset($groupUsers[$pos]);

			$groups = $this->groupsOfGroup($groupname);

			$userString = self::convertGroupsUsersToString($groups, $groupUsers);
			$this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
		}
		else
		{
			// User is not in group.
			return true;
		}
		return true;
	}

	/**
	 * Removes the given group from group.
	 *
	 * @param string $subgroupname
	 * @param string $groupname
	 *
	 * @return bool
     *
     * 从组中移除子组
	 */
	public function removeSubgroupFromGroup($subgroupname, $groupname)
	{
		$groupGroups = $this->groupsOfGroup($groupname);

		// Search the user in array.
		$pos = array_search($subgroupname, $groupGroups);
		if ($pos !== FALSE)
		{
			// Remove the group from array.
			unset($groupGroups[$pos]);

			$users = $this->usersOfGroup($groupname);

			$userString = self::convertGroupsUsersToString($groupGroups, $users);
			$this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
		}
		else
		{
			// Group is not in group.
			return true;
		}
		return true;
	}

	/**
	 * Removes the given $groupname from $repository.
	 *
	 * @param string $groupname
	 * @param string $repository
	 *
	 * @return bool
     *
     * 从仓库中移除组
	 */
	public function removeGroupFromRepository($groupname, $repository)
	{
		// Does the repo config exists?
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}

		$groupname = '@'.$groupname;
		return $this->config->removeValue($repository, $groupname);
	}

	/**
	 * Removes the given $username from $repository.
	 *
	 * @param string $username
	 * @param string $repository
	 *
	 * @return bool
     * 从仓库中移除用户
	 */
	public function removeUserFromRepository($username, $repository)
	{
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}
		return $this->config->removeValue($repository, $username);
	}

	/**
	 * Gets to know whether the user is assigned to a specified
	 * repository path (optional: with specific permission.)
	 *
	 * @param string $username
	 * @param string $repository
	 * @param string $permission
	 *
	 * @return bool
     * 判断用户是否分配该仓库的权限
	 */
	public function isUserAssignedToRepository($username, $repository, $permission=null)
	{
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}

		if ($this->config->getValueExists($repository, $username))
		{
			if ($permission == null)
			{
				return true;
			}
			else
			{
				// Provide for specific permission.
				if ($this->config->getValue($repository, $username) == $permission)
				{
					return true;
				}
				return false;
			}
		}

		return false;
	}

	/**
	 * Gets to know whether the group is assigned to a specified
	 * repository path (optional: with specific permission.)
	 *
	 * @param string $username
	 * @param string $repository
	 * @param string $permission
	 *
	 * @return bool
     *
     * 判断组是否分配该仓库的权限
	 */
	public function isGroupAssignedToRepository($groupname, $repository, $permission=null)
	{
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}

		$groupname = $this->GROUP_SIGN . $groupname;

		if ($this->config->getValueExists($repository, $groupname))
		{
			if ($permission == null)
			{
				return true;
			}
			else
			{
				// Provide for specific permission.
				if ($this->config->getValue($repository, $groupname) == $permission)
				{
					return true;
				}
				return false;
			}
		}

		return false;
	}

	/**
	 * Assigns a user directly to a repository with permissions.
	 *
	 * @param string $username
	 * @param string $repository
	 * @param string $permission
	 *
	 * @return bool
     *
     * 给用户分配权限
	 */
	public function addUserToRepository($username, $repository, $permission)
	{
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}

		$this->config->setValue($repository, $username, $permission);
		return true;
	}


	/**
	 * Assigns a group directly to a repository with permissions.
	 *
	 * @param string $groupname
	 * @param string $repository
	 * @param string $permission
	 *
	 * @return bool
     *
     * 给组分配权限
	 */
	public function addGroupToRepository($groupname, $repository, $permission)
	{
		if (!$this->repositoryPathExists($repository))
		{
			return false;
		}

		$groupname = $this->GROUP_SIGN . $groupname;
		$this->config->setValue($repository, $groupname, $permission);
		return true;
	}

	/**
	 * Gets an array which holds all permissions of a specific user.<br>
	 * Returning array example:
	 *
	 * array(
	 * 		array(	0 => "repo_path:/",		// Access-Path
	 * 				1 => "rw",				// Permission
	 * 				2 => "group1"			// Derived group, '*' or empty.
	 * 		),
	 * 		array(	0 => ....
	 * 		)
	 * )
	 *
	 * @param string $username
	 * @param bool $resolveGroups (default=true) Indicates whether groups and *-user should be resolved, too.
	 * @param string $filterRepository (default=null) Restricts the returning array to the given repository.
	 *
	 * @return array See method description for details.
     *
     * 获取所有用户的权限列表
	 */
	public function permissionsOfUser($username, $resolveGroups = true, $filterRepository = null)
	{
		$ret = array();

		// Iterate all repository paths.
		$repositories = $this->repositories();
		foreach ($repositories as $repository)
		{
			// If !null than only prove the $filterRepository.
			if ($filterRepository != null && $filterRepository != $repository)
			{
				continue;
			}

			// Get the permission of the user.
			$permission = $this->config->getValue($repository, $username);
			if ($permission !== null)
			{
				$ret[] = array($repository, $permission, '');
			}

			if ($resolveGroups)
			{
				// Iterate all groups which are directly assigned to the repository
				// and check whether the '$username' is a member.
				$groups = $this->groupsOfRepository($repository);
				foreach ($groups as $g)
				{
					if ($this->isUserInGroup($g, $username))
					{
						$g2 = $this->GROUP_SIGN . $g;
						$permission = $this->config->getValue($repository, $g2);
						if ($permission !== null)
						{
							$ret[] = array($repository, $permission, $g);
						}
					}
				}

				// Get the all-user permissions.
				$permission = $this->config->getValue($repository, $this->SIGN_ALL_USERS);
				if ($permission !== null)
				{
					$ret[] = array($repository, $permission, $this->SIGN_ALL_USERS);
				}
			}

		} // foreach ($repositories)

		return $ret;
	}

	/**
	 * Gets an array which holds all permissions of a specific group.<br>
	 * Returning array example:
	 *
	 * array(
	 * 		array(	0 => "repo_path:/",		// Access-Path
	 * 				1 => "rw",				// Permission
	 * 				2 => "group1"			// Derived group or empty.
	 * 		),
	 * 		array(	0 => ....
	 * 		)
	 * )
	 *
	 * @param string $groupname
	 * @param bool $resolveGroups (default=true) Indicates whether groups should be resolved, too.
	 * @param string $filterRepository (default=null) Restricts the returning array to the given repository.
	 *
	 * @return array See method description for details.
     *
     * 获取组具备的权限
	 */
	public function permissionsOfGroup($groupname, $resolveGroups = true, $filterRepository = null)
	{
		$ret = array();
		$groupname_internal = $this->GROUP_SIGN . $groupname;

		// Iterate all repository paths.
		$repositories = $this->repositories();
		foreach ($repositories as $repository)
		{
			// If !null than only prove the $filterRepository.
			if ($filterRepository != null && $filterRepository != $repository)
			{
				continue;
			}

			// Get the direct permission of the group.
			$permission = $this->config->getValue($repository, $groupname_internal);
			if ($permission !== null)
			{
				$ret[] = array($repository, $permission, '');
			}

			if ($resolveGroups)
			{
				// TODO: Iterate all groups, and check whether the current
				// group is a member of one of these.
			}

		} // foreach ($repositories)

		return $ret;
	}

	/**
	 * Convert list of groups and users to string to associate a group.
	 *
	 * @param array $groups
	 * @param array $users
	 * @return string
	 * @see http://svnbook.red-bean.com/en/1.7/svn.serverconfig.pathbasedauthz.html
     *
     * 将用户列表或组列表转换成字符串
	 */
	private static function convertGroupsUsersToString(array $groups, array $users)
	{
		if (!$groups && !$users)
			return '';

		// anonymous functions works on PHP 5.3 or higher
		array_walk($groups, function(&$item){
			$item = '@'.$item;
		});

		$string = join(',', array_merge($groups, $users));
		return $string;
	}
}
?>