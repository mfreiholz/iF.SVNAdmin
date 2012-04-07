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
namespace svnadmin\providers\ldap;

class LdapUserViewProvider extends \IF_AbstractLdapConnector
	implements	\svnadmin\core\interfaces\IUserViewProvider,
				\svnadmin\core\interfaces\IGroupViewProvider
{
	/**
	 * Indicates whether the "init()" method has been called.
	 * @var bool
	 */
	private $m_init_done = false;

	/**
	 * Indicates whether the "update()" method has been called.
	 * @var bool
	 */
	private $m_update_done = false;

	/**
	 * LDAP service account, which is used for all requests to the LDAP server.
	 * @var string
	 */
	protected $bind_dn;

	/**
	 * LDAP service account password for the "$bind_dn".
	 * @var string
	 */
	protected $bind_password;

	/**
	 * Host address of the LDAP server.
	 * Format: ldap[s]://<address>[:<port]/
	 * @var string
	 */
	protected $host_address;

	/**
	 * Port of the LDAP server, if not given in "$host_address".
	 * @var int
	 */
	protected $host_port = 0;

	/**
	 * LDAP connection protocol version.
	 * @var unknown_type
	 */
	protected $host_protocol_version;

	/*
	 * Settings to find users.
	 */

	/**
	 * The base path of users.
	 * @var string
	 */
	protected $users_base_dn;

	/**
	 * The base filter to identify a entry as user.
	 * e.g.: "objectClass=person"
	 * @var string
	 */
	protected $users_search_filter;

	/**
	 * The attributes of a user, which should be returned on all requests.
	 * (The name of the attribute which SVN(apache) uses to authenticate the user)
	 * @var array
	 */
	protected $users_attributes;

	/*
	 * Settings to find groups.
	 */

	/**
	 * The base path of groups.
	 * @var string
	 */
	protected $groups_base_dn;

	/**
	 * The base filter to identify a entry as group. Example: "objectClass=group"
	 * @var string
	 */
	protected $groups_search_filter;

	/**
	 * The attributes of a group, which should be returned on all requests.
	 * (The name of the group, which will be shown in the application view)
	 * @var array
	 */
	protected $groups_attributes;

	/**
	 * The attribute name of a group, which identifies the member association.
	 * e.g.: "member"
	 * @var string
	 */
	protected $groups_to_users_attribute;

	/**
	 *
	 * e.g.: "dn"
	 * @var string
	 */
	protected $groups_to_users_attribute_value;

	/**
	 * Holds the singelton instance of this class.
	 * @var \svnadmin\providers\ldap\LdapUserViewProvider
	 */
	private static $m_instance = null;

	/**
	 * Constructor.
	 * Initializes the instance by the Engine configuration.
	 */
	public function __construct()
	{
		$E = \svnadmin\core\Engine::getInstance();
		$cfg = $E->getConfig();

		$this->host_address = $cfg->getValue("Ldap", "HostAddress");
		$this->host_protocol_version = $cfg->getValue("Ldap", "ProtocolVersion");
		$this->bind_dn = $cfg->getValue("Ldap", "BindDN");
		$this->bind_password = $cfg->getValue("Ldap", "BindPassword");

		$this->users_base_dn = $cfg->getValue("Users:ldap", "BaseDN");
		$this->users_search_filter = $cfg->getValue("Users:ldap", "SearchFilter");
		$this->users_attributes = $cfg->getValue("Users:ldap", "Attributes");
		$this->users_attributes = explode(",", $this->users_attributes);

		$this->groups_base_dn = $cfg->getValue("Groups:ldap", "BaseDN");
		$this->groups_search_filter = $cfg->getValue("Groups:ldap", "SearchFilter");
		$this->groups_attributes = $cfg->getValue("Groups:ldap", "Attributes");
		$this->groups_attributes = explode(",", $this->groups_attributes);
		$this->groups_to_users_attribute = $cfg->getValue("Groups:ldap", "GroupsToUserAttribute");
		$this->groups_to_users_attribute_value = $cfg->getValue("Groups:ldap", "GroupsToUserAttributeValue");
	}

	/**
	 * Gets the "singelton" instance of this class.
	 *
	 * @return \svnadmin\providers\ldap\LdapUserViewProvider
	 */
	public static function getInstance()
	{
		if (self::$m_instance == null)
			self::$m_instance = new LdapUserViewProvider();
		return self::$m_instance;
	}

	// Required to execute tests on settings page.
	public function setConnectionInformation($hostAddress, $hostPort, $hostProtocol, $bindDn, $bindPassword)
	{
		$this->host_address = $hostAddress;
		$this->host_port = $hostPort;
		$this->host_protocol_version = $hostProtocol;
		$this->bind_dn = $bindDn;
		$this->bind_password = $bindPassword;
	}

	// Required to execute tests on settings page.
	public function setUserViewInformation($usersBaseDn, $usersSearchFilter, $usersAttributes)
	{
		$this->users_base_dn = $usersBaseDn;
		$this->users_search_filter = $usersSearchFilter;
		$this->users_attributes = explode(",", $usersAttributes);
	}

	// Required to execute tests on settings page.
	public function setGroupViewInformation($groupsBaseDn, $groupsSearchFilter, $groupsAttributes, $groupsMemberAttribute, $groupsMemberAttributeValueAttribute)
	{
		$this->groups_base_dn = $groupsBaseDn;
		$this->groups_search_filter = $groupsSearchFilter;
		$this->groups_attributes = explode(",", $groupsAttributes);
		$this->groups_to_users_attribute = $groupsMemberAttribute;
		$this->groups_to_users_attribute_value = $groupsMemberAttributeValueAttribute;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IProvider::init()
	 */
	public function init()
	{
		if (!$this->m_init_done)
		{
			$this->m_init_done = true;

			if (parent::connect($this->host_address, $this->host_port, $this->host_protocol_version) === false)
				return false;

			if (parent::bind($this->bind_dn, $this->bind_password) === false)
				return false;
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IViewProvider::isUpdateable()
	 */
	public function isUpdateable()
	{
		return true;
	}

	/**
	 * Update the SVNAuthFile with data from LDAP server.
	 * @see svnadmin\core\interfaces.IViewProvider::update()
	 */
	public function update()
	{
		// This class is responsible for users and groups.
		// On update this function should only be called one-times.
		if (!$this->m_update_done)
		{
			$this->m_update_done = true;

			$E = \svnadmin\core\Engine::getInstance();
			$autoRemoveUsers = $E->getConfig()->getValueAsBoolean("Update:ldap", "AutoRemoveUsers", true);
			$autoRemoveGroups = $E->getConfig()->getValueAsBoolean("Update:ldap", "AutoRemoveGroups", true);

			$this->updateSvnAuthFile($autoRemoveUsers, $autoRemoveGroups);
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IUserViewProvider::getUsers()
	 */
	public function getUsers($withStarUser=true)
	{
		$ret = array();
		$up_name = strtolower($this->users_attributes[0]);

		// Search users in LDAP.
		$ldapUsers = $this->p_getUserEntries();
		$ldapUsersLen = count($ldapUsers);

		for ($i = 0; $i < $ldapUsersLen; ++$i)
		{
			$u = new \svnadmin\core\entities\User;
			$u->id = $ldapUsers[$i]->dn;
			$u->name = $ldapUsers[$i]->$up_name;
			$ret[] = $u;
		}

		// Staticly get the '*' user.
		if ($withStarUser)
		{
			$oUAll = new \svnadmin\core\entities\User;
			$oUAll->id = '*';
			$oUAll->name = '*';
			$ret[] = $oUAll;
		}
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IUserViewProvider::userExists()
	 */
	public function userExists($objUser)
	{
		// Create filter. Example: sAMAccountName=ifmanuel
		$user_name_filter = $this->users_attributes[0] . '=' . $objUser->name;
		$final_filter = '(&('.$user_name_filter.')'.$this->users_search_filter.')';

		// Search for a user, where the 'users_attributes' equals the $objUser->name.
		$found = parent::objectSearch($this->connection, $this->users_base_dn, $final_filter, $this->users_attributes, 1);

		if (!is_array($found) || count($found) <= 0)
			return false;

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IUserViewProvider::authenticate()
	 */
	public function authenticate($objUser, $password)
	{
		// Create filter. Example: sAMAccountName=ifmanuel
		$user_name_filter = $this->users_attributes[0] . '=' . $objUser->name;
		$final_filter = '(&('.$user_name_filter.')'.$this->users_search_filter.')';

		// Search for a user, where the 'users_attributes' equals the $objUser->name.
		$found = parent::objectSearch( $this->connection, $this->users_base_dn, $final_filter, $this->users_attributes, 1 );

		if (!is_array($found) || count($found) <= 0)
			return false; // User not found.

		// The user has been found.
		// Get the dn of the user and authenticate him/her now.
		return \IF_AbstractLdapConnector::authenticateUser($this->host_address, $this->host_port, $found[0]->dn, $password, $this->host_protocol_version);
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroups()
	 */
	public function getGroups()
	{
		$ret = array();
		$group_name_property = strtolower($this->groups_attributes[0]);

		// Get groups from LDAP server.
		$ldapGroups = $this->p_getGroupEntries();
		$ldapGroupsLen = count($ldapGroups);

		for ($i = 0; $i < $ldapGroupsLen; $i++)
		{
			$o = new \svnadmin\core\entities\Group;
			$o->id = $ldapGroups[$i]->dn;
			$o->name = $ldapGroups[$i]->$group_name_property;
			$ret[] = $o;
		}
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::groupExists()
	 */
	public function groupExists($objGroup)
	{
		// Create filter. Example: sAMAccountName=_ISVNADMIN
		$group_name_filter = $this->groups_attributes[0] . '=' . $objGroup->name;
		$final_filter = '(&('.$group_name_filter.')'.$this->groups_search_filter.')';

		// Search for a group, where the 'groups_attributes' equals the $objGroup->name.
		$found = parent::objectSearch($this->connection, $this->groups_base_dn, $final_filter, $this->groups_attributes, 1);

		if (!is_array($found) || count($found) <= 0)
			return false;

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getGroupsOfUser()
	 */
	public function getGroupsOfUser($objUser)
	{
		$ret = array();

		// First, we have to find the user entry in the LDAP.
		$userEntry = $this->p_findUserEntry($objUser);
    	$propUserId = strtolower($this->groups_to_users_attribute_value);

		// Create filter to find all group CNs which contains the usersEntry DN as 'member'.
		$filter = $this->groups_to_users_attribute.'='.$userEntry->$propUserId;
		$filter = '(&('.$filter.')'.$this->groups_search_filter.')';

		// Execute search.
		$found = parent::objectSearch($this->connection, $this->groups_base_dn, $filter, $this->groups_attributes, 0);

		if (!is_array($found) || count($found) <= 0)
			return $ret;

		$propGroupName = strtolower($this->groups_attributes[0]);

		foreach ($found as $stdObj)
		{
			$o = new \svnadmin\core\entities\Group;
			$o->name = $stdObj->$propGroupName;
			$ret[] = $o;
		}
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::getUsersOfGroup()
	 */
	public function getUsersOfGroup($objGroup)
	{
		$ret = array();

		// Create filter to find all members of the given group.
		$filter = $this->groups_attributes[0].'='.$objGroup->name;
		$filter = '(&('.$filter.')'.$this->groups_search_filter.')';

		// We need the 'groups_to_users_attribute'.
		$att = array();
		$att[] = $this->groups_to_users_attribute;

		// Execute search.
		$found = parent::objectSearch($this->connection, $this->groups_base_dn, $filter, $att, 1);

		if (!is_array($found) || count($found) <= 0)
			return $ret;

		// Now we have to match the value which is saved in member to the user entry.
		$propName = strtolower($this->groups_to_users_attribute);
		$propName2 = strtolower($this->users_attributes[0]);

		if (!property_exists($found[0], $propName))
		{
			// The group has no members.
			// ...
		}
		else if (is_array($found[0]->$propName))
		{
			// Multiple members.
			foreach ($found[0]->$propName as $value)
			{
				// Find the user entry.
				$userEntry = self::p_resolveGroupMemberId($value);

				if ($userEntry != null)
				{
					// Create User-object from the entry.
					$u = new \svnadmin\core\entities\User;
					$u->name = $userEntry->$propName2;
					$ret[] = $u;
				}
			}
		}
		else if ($found[0]->$propName != null)
		{
			// Its a single member.
			// Find the user entry.
			$userEntry = self::p_resolveGroupMemberId($found[0]->$propName);

			if ($userEntry != null)
			{
				// Create User-object from the entry.
				$u = new \svnadmin\core\entities\User;
				$u->name = $userEntry->$propName2;
				$ret[] = $u;
			}
		}
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see svnadmin\core\interfaces.IGroupViewProvider::isUserInGroup()
	 *
	 * @todo Test me
	 */
	public function isUserInGroup($objUser, $objGroup)
	{
		// Get the user and group entry.
		$userEntry = $this->p_findUserEntry($objUser);
		$groupEntry = $this->p_findGroupEntry($objGroup);

		$propGroupName = strtolower($this->groups_attributes[0]);

		// Create filter to find the user as attribute inside the group.
		$filter_user = $this->groups_to_users_attribute.'='.$userEntry->dn;
		$filter_group = $this->groups_attributes[0].'='.$groupEntry->$propGroupName;
		$filter = '(&('.$filter_user.')('.$filter_group.')'.$this->groups_search_filter.')';

		// Execute search.
		$found = parent::objectSearch($this->connection, $this->groups_base_dn, $filter, $this->groups_attributes, 1);

		if (!is_array($found) || count($found) <= 0)
			return false;

		return true;
	}

	/**************************************************************************
	 * Protected helper methods.
	 *************************************************************************/

	/**
	 * Gets all user LDAP entries from server.
	 *
	 * @return array<stdClass>
	 */
	protected function p_getUserEntries()
	{
		// The standard attributes.
		$attributes = $this->users_attributes;

		// Include the attribute which is used in the "member" attribute of a group-entry.
		$attributes[] = $this->groups_to_users_attribute_value;

		return parent::objectSearch($this->connection, $this->users_base_dn, $this->users_search_filter, $attributes, 0);
	}

	/**
	 * Gets all group LDAP entries from server.
	 *
	 * @return array<stdClass>
	 */
	protected function p_getGroupEntries($includeMembers = false)
	{
		$attributes = $this->groups_attributes;

		if ($includeMembers)
			$attributes[] = $this->groups_to_users_attribute;

		return parent::objectSearch($this->connection, $this->groups_base_dn, $this->groups_search_filter, $attributes, 0);
	}

	/**
	 * Searches the LDAP entry of the given user.
	 *
	 * @param \svnadmin\core\entities\User $objUser
	 *
	 * @return stdClass or NULL
	 */
	protected function p_findUserEntry(\svnadmin\core\entities\User $objUser)
	{
		// Create filter. Example: sAMAccountName=ifmanuel
		$user_name_filter = $this->users_attributes[0] . '=' . $objUser->name;
		$final_filter = '(&('.$user_name_filter.')'.$this->users_search_filter.')';

		$attributes = $this->users_attributes;
		$attributes[] = $this->groups_to_users_attribute_value;

		$found = parent::objectSearch($this->connection, $this->users_base_dn, $final_filter, $attributes, 1);

		if (!is_array($found) || count($found) <= 0)
			return NULL;

		return $found[0];
	}

	/**
	 * Searches the LDAP entry of the given group.
	 *
	 * @param \svnadmin\core\entities\Group
	 *
	 * @return stdClass or NULL
	 */
	protected function p_findGroupEntry(\svnadmin\core\entities\Group $objGroup)
	{
		// Create filter. Example: sAMAccountName=_ISVNADMIN
		$filter = $this->groups_attributes[0] .'=' . $objGroup->name;
		$filter = '(&('.$filter.')'.$this->groups_search_filter.')';

		$attributes = $this->groups_attributes;
		$attributes[] = $this->groups_to_users_attribute;

		// Execute search.
		$found = parent::objectSearch($this->connection, $this->groups_base_dn, $filter, $att, 1);

		if (!is_array($found) || count($found) <= 0)
			return NULL;

		return $found[0];
	}

	/**
	 * Searches for a user-entry based on the member-id from the group.
	 *
	 * @param string The member id which is associated to a group (mostyl the DN)
	 *
	 * @return stdClass User-entry or NULL
	 */
	protected function p_resolveGroupMemberId($memberId)
	{
		// Create filter.
		$filter = $this->groups_to_users_attribute_value.'='.$memberId;
		$filter = '(&('.$filter.')'.$this->users_search_filter.')';

		// Execute search.
		$found = parent::objectSearch($this->connection, $this->users_base_dn, $filter, $this->users_attributes, 1);

		if (!is_array($found) || count($found) <= 0)
		{
			error_log("Can not resolve member ID. member-id=$memberId; filter=$filter;");
			return null;
		}

		return $found[0];
	}

	/**
	 * Updates the SVNAuthFile with Users and Groups from LDAP server.
	 */
	public function updateSvnAuthFile($autoRemoveUsers=true, $autoRemoveGroups=true)
	{
		$this->init();
		$E = \svnadmin\core\Engine::getInstance();
		
		// Increase max_execution_time for big LDAP structures.
		$maxTime = intval(ini_get('max_execution_time'));
		if ($maxTime != 0 && $maxTime < 300) {
			@ini_set('max_execution_time', 300);
		}

		try {
			// @todo Backup file.

			// Step 1
			// Load the current SVNAuthFile and remove/reset all existing groups.

			// Load file.
			$svnAuthFilePath = $E->getConfig()->getValue("Subversion", "SVNAuthFile");
			$svnAuthFile = new \IF_SVNAuthFileC($svnAuthFilePath);
			$svnAuthFileOld = new \IF_SVNAuthFileC($svnAuthFilePath);

			// Remove groups.
			$svnAuthFileGroups = $svnAuthFile->groups();
			foreach ($svnAuthFileGroups as $g)
			{
				$svnAuthFile->deleteGroup($g);
			}

			// Step 2
			// Get all users and groups from LDAP server.

			// Users.
			$users = array();
			$users = $this->p_getUserEntries();

			// Groups.
			$groups = array();
			$groups = $this->p_getGroupEntries(true);

			// Step 3
			// Iterate all groups which has been fetched from LDAP server
			// and create them in the SVNAuthFile. Addionally associate
			// all users to a group which are defined as member of a it.
			//
			// @todo Add the Realname or DN of a user as Alias to the SVNAuthFile.

			// Property name of a Group-Entry which holds the group's name.
			$gp_name = strtolower($this->groups_attributes[0]);

			// Property name of a Group-Entry which holds the member-id (DN).
			$gp_member_id = strtolower($this->groups_to_users_attribute);

			// Property name of a User-Entry which holds the user's name.
			$up_name = strtolower($this->users_attributes[0]);

			// Property name of a User-Entry which holds the value which is assigned in a Group-Entry as Member-ID.
			$up_id = strtolower($this->groups_to_users_attribute_value);

			foreach ($groups as $g)
			{
				if (!property_exists($g, $gp_name))
					continue; // The group-name property doesn't exist.

				try {
					// Create group in SVNAuthFile. (throws Exception)
					$svnAuthFile->createGroup($g->$gp_name);
				}
				catch (\Exception $except) {
					$E->addException($except);
					continue;
				}

				// Find members.
				if (!property_exists($g, $gp_member_id))
				{
					// No members.
					// @todo Should we delete empty groups from overview?
				}
				elseif (is_array($g->$gp_member_id))
				{
					// Multiple members.
					foreach ($g->$gp_member_id as $member_id)
					{
						// Get name of the member.
						foreach ($users as $u)
						{
							if ($u->$up_id == $member_id)
							{
								// Add user to SVNAuthFile-Group.
								$svnAuthFile->addUserToGroup($g->$gp_name, $u->$up_name);
								break;
							}
						}
					}
				}
				elseif (is_string($g->$gp_member_id))
				{
					// One member.
					$member_id = $g->$gp_member_id;

					// Get name of the member.
					foreach ($users as $u)
					{
						if ($u->$up_id == $member_id)
						{
							// Add user to SVNAuthFile-Group.
							$svnAuthFile->addUserToGroup($g->$gp_name, $u->$up_name);
							break;
						}
					}
				}
			} // foreach($groups)

			// Step 4
			// Save new SVNAuthFile to disk.
			$svnAuthFile->save();


			// Step 5
			// Compare with previous file to revoke AccessPath permissions of
			// deleted groups and users.
			//
			// We need to reset the Provider object, because it holds the
			// SVNAuthFile and should be reloaded, because of the cahnges
			// above.
			$apEditProvider = $E->getProvider(PROVIDER_ACCESSPATH_EDIT);
			$apEditProvider->reset();

			$removedUsers = array();
			$removedGroups = array();

			// Collect removed groups.
			// Groups which are in the old file but not in the new one.
			foreach ($svnAuthFileOld->groups() as $g)
			{
				if (!$svnAuthFile->groupExists($g))
				{
					// The group $g is not in the new configuration (Removed from LDAP).
					$removedGroups[] = $g;

					if ($autoRemoveGroups)
					{
						try {
							$apEditProvider->removeGroupFromAllAccessPaths(
								new \svnadmin\core\entities\Group($g, $g)
							);
							$E->addMessage(tr("The group <b>%0</b> has been removed from LDAP. Removed all assigned permissions.", array($g)));
						}
						catch (\Exception $e) {
							$E->addException($e);
						}
					}
				}
			}

			// Collect removed users and groups with direct associated
			// Access-Path permissions and revoke the permissions.
			foreach ($svnAuthFile->repositories() as $r)
			{
				// Users.
				foreach ($svnAuthFile->usersOfRepository($r) as $u)
				{
					if (!$this->userExists(new \svnadmin\core\entities\User($u, $u)))
					{
						// The user has direct AccessPath permissions but does
						// not exist on LDAP server.
						$removedUsers[] = $u;

						if ($autoRemoveUsers)
						{
							// Revoke permissions.
							try {
								$apEditProvider->removeUserFromAccessPath(
									new \svnadmin\core\entities\User($u, $u),
									new \svnadmin\core\entities\AccessPath($r)
								);
								$E->addMessage(tr("The user <b>%0</b> doesn't exist anymore. Removed direct Access-Path permission to <b>%1</b>", array($u, $r)));
							}
							catch (\Exception $e) {
								$E->addException($e);
							}
						}
					}
				} // foreach (users)

				// Groups.
				foreach ($svnAuthFile->groupsOfRepository($r) as $g)
				{
					// We can check against the new SVNAuthFile, because the
					// containing groups are updated from LDAP.
					//if (!$this->groupExists(new \svnadmin\core\entities\Group($g, $g)))
					if (!$svnAuthFile->groupExists($g))
					{
						$removedGroups[] = $g;

						if ($autoRemoveGroups)
						{
							// Revoke permissions.
							try {
								$apEditProvider->removeGroupFromAccessPath(
									new \svnadmin\core\entities\Group($g, $g),
									new \svnadmin\core\entities\AccessPath($r)
								);
								$E->addMessage(tr("The group <b>%0</b> doesn't exist anymore. Removed direct Access-Path permission to <b>%1</b>", array($g, $r)));
							}
							catch (\Exception $e) {
								$E->addException($e);
							}
						}
					}
				} // foreach (groups)

			} // foreach (repositories)

			// Save changes made to "$apEditProvider".
			$apEditProvider->save();
		}
		catch (\Exception $ex) {
			throw $ex;
		}
	}
}
?>