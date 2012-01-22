<?php
namespace svnadmin\core\acl
{
  /**
   * 
   * Manages the permission of roles to different modules.
   * @author Manuel Freiholz
   *
   */
  class FSAclManager
    implements \svnadmin\core\interfaces\IAclManager
  {
    /**
     * Indicates whether the init-method was called.
     * @var bool
     */
    private $init_done;

    /**
     * Holds the acl object.
     * @var IF_ACL
     */
    private $acl;
    
    /**
     * Holds the path to the user-role-assignment-file.
     * @var string
     */
    private $user_role_file;
    
    /**
     * Holds the parsed data of the $user_role_file.
     * @var array
     */
    private $assignments;

    /**
     * The prefix of a section which makes the section to an access path section,
     * which holds the assignment projects managers as keys.
     * @var string
     */
    public $path_postfix = ":AccessPaths";

    /**
     * The role name of the project manager role.
     * @var string
     */
    public $project_admin_role_name = "Project-Manager";

    /**
     * The role name of the administrator role.
     * @var string
     */
    public $administrator_role_name = "Administrator";

    /**
     * Creates a new instance of the FSAclManager.
     * The object serializes the ACL's to the given $file.
     * @param string $file
     */
    public function __construct($userRoleFile)
    {
      $this->init_done = FALSE;
      $this->user_role_file = (string)$userRoleFile;
      $this->acl = NULL;
      $this->assignments = array();
    }

    /**
     * Loads the ACL and assignments of users from file,
     * if the function is called the first time.
     */
    public function init()
    {
      if ($this->init_done == FALSE)
      {
        $this->init_done = TRUE;
        self::load();
      }
    }

    /**
     * Gets all available roles.
     * @return array<IF_ACLRole>
     */
    public function getRoles()
    {
      // Convert the IF_ACLRole objects to internally used Role objects.
      $rolesArray = array();
      $aclRoles = $this->acl->getRoles();
      foreach ($aclRoles as $aclRoleObj)
      {
        $o = new \svnadmin\core\entities\Role();
        $o->name = $aclRoleObj->getName();
        $o->description = $aclRoleObj->getDescription();
        $rolesArray[] = $o;
      }
      return $rolesArray;
    }

    /**
     * Checks whether the user has permission.
     * @param User $objUser
     * @param string $module
     * @param string $action
     */
    public function hasPermission($objUser, $module, $action)
    {
      // Get roles of user.
      $roles = self::getRolesOfUser($objUser);
      
      // Check all roles for permission, until one has the permission.
      foreach ($roles as &$roleObj)
      {
        if ($this->acl->hasPermission($roleObj->getName(), $module, $action))
          return true;
      }

      // FIXME This is a really dirty way to manage the rights of the project manager. I dont like it...
      // Project-Manager
      $isProjectManager = self::isUserAccessPathManager($objUser->name);
      if ($isProjectManager)
      {
        $tmpAcl = clone $this->acl;
        $n = $this->project_admin_role_name;
        $tmpAcl->addRole(new \IF_ACLRole($n, "Can be assigned to Access-Paths as project manager."), array("User"));
        $tmpAcl->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_VIEW));
        $tmpAcl->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_VIEW));
        $tmpAcl->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_VIEW));
        $tmpAcl->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_ADD));
        $tmpAcl->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_DELETE));
        $tmpAcl->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_ASSIGN));
        $tmpAcl->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_UNASSIGN));
        $tmpAcl->addRule($n, \ACL_MOD_REPO, array(\ACL_ACTION_VIEW));
        $tmpAcl->addRule($n, \ACL_MOD_PROJECTMANAGER, array(\ACL_ACTION_VIEW));

        if ($tmpAcl->hasPermission($this->project_admin_role_name, $module, $action))
          return true;
      }
      return false;
    }

    /**
     * Checks whether a user is assigned to a role.
     * @param string $username
     * @param string $rolename
     * @return bool
     */
    public function userHasRole($username, $rolename)
    {
      if ($this->assignments == null)
        return false;

      if (isset($this->assignments[$username]) && is_array($this->assignments[$username]))
      {
        if (isset($this->assignments[$username][$rolename]))
          return true;
      }
      return false;
    }

    /**
     * Gets the roles of the user.
     * @param User $objUser
     * @return array<\svnadmin\core\entities\Role>
     */
    public function getRolesOfUser($objUser)
    {
      $roles = array();
      if ($this->assignments == null)
        return $roles;

      if (isset($this->assignments[$objUser->getName()]) && is_array($this->assignments[$objUser->getName()]))
      {
        foreach ($this->assignments[$objUser->getName()] as $rolename => &$noval)
        {
          $roleObj = $this->acl->getRoleByName($rolename);
          if ($roleObj != NULL)
          {
            $o = new \svnadmin\core\entities\Role();
            $o->name = $roleObj->getName();
            $o->description = $roleObj->getDescription();
            $roles[] = $o;
          }
        }
      }
      return $roles;
    }

    /**
     * Assigns the given user to the given role.
     * @param <type> $objUser
     * @param <type> $objRole
     * @return bool
     */
    public function assignUserToRole($objUser, $objRole)
    {
      // First check, whether the section of the user already exists.
      if (!isset($this->assignments[$objUser->getName()]))
      {
        $this->assignments[$objUser->getName()] = array();
      }
      else
      {
        // Check whether the user is already assigned to this role.
        if (isset($this->assignments[$objUser->getName()][$objRole->getName()]))
        {
          return true;
        }
      }

      // Assign the user now.
      $this->assignments[$objUser->getName()][$objRole->getName()] = "";
      return true;
    }

    /**
     * Removes the user from a role.
     * @param <type> $objUser
     * @param <type> $objRole
     * @return bool
     */
    public function removeUserFromRole($objUser, $objRole)
    {
      // First check, whether the section of the user exists.
      if (!isset($this->assignments[$objUser->getName()]))
      {
        // The user is not assigned to any role.
        return true;
      }

      // Check whether the assignment exists.
      if (isset($this->assignments[$objUser->getName()][$objRole->getName()]))
      {
        unset($this->assignments[$objUser->getName()][$objRole->getName()]);
      }

      // Count the number of left roles from the user.
      $leftRoleCount = count($this->assignments[$objUser->getName()]);
      if ($leftRoleCount < 1)
        unset($this->assignments[$objUser->getName()]);// Remove the user section.

      return true;
    }

    /**
     * Removes all role associations of a user.
     * @param string $username
     * @return bool Returns FALSE if the user had no role, otherwise TRUE.
     */
    public function removeAllRolesFromUser($username)
    {
      if (!isset($this->assignments[$username]))
        return false;

      unset($this->assignments[$username]);
      return true;
    }

    /**
     * Checks whether at least one administrator is defined.
     * @return bool
     */
    public function hasAdminDefined()
    {
      $cnt = count($this->assignments);
      if ($cnt <= 0)
        return false;

      foreach ($this->assignments as $username => &$keys)
      {
        if (is_array($this->assignments[$username]))
        {
          foreach ($this->assignments[$username] as $rolename => &$noval)
          {
            if ($rolename == $this->administrator_role_name)
              return true;
          }
        }
      }
      return false;
    }

    /**
     * Checks whether a user is a manager for any access-path.
     * @param string $username
     * @param bool
     */
    public function isUserAccessPathManager($username)
    {
      if ($this->assignments == null)
        return false;

      $section = $username.$this->path_postfix;
      return isset($this->assignments[$section]) ? true : false;
    }

    /**
     * Gets all defined access paths of a user.
     * @param string $username
     * @return array<RepositoryPath>
     */
    public function getAccessPathsOfUser($username)
    {
      $list = array();
      if ($this->assignments == null)
        return $list;

      // Name of the section: (ifmanuel:AccessPaths)
      $section = $username.$this->path_postfix;

      if (!isset($this->assignments[$section]))
        return $list;

      $idx = 0;
      while (isset($this->assignments[$section][$idx]))
      {
        $o = new \svnadmin\core\entities\AccessPath();
        $o->path = $this->assignments[$section][$idx];
        $list[] = $o;
        $idx++;
      }
      return $list;
    }
    
    /**
     * Gets all project managers of the given $path.
     * @param $path The Access-Path.
     * @return array List of usernames.
     */
    public function getUsersOfAccessPath($path)
    {
    	$list = array();
    	if ($this->assignments == null)
    	  return $list;
    	  
    	// Iterate all AccessPaths sections and search for matching of $path.
    	foreach ($this->assignments as $sec => &$key)
    	{
    		$pos = null;
    		if (($pos=strpos($sec, $this->path_postfix)) !== false)
    		{
    			$idx = 0;
    			while (isset($this->assignments[$sec][$idx]))
    			{
    				if ($this->assignments[$sec][$idx] == $path)
    				{
    					// Extract username from section head.
    					$username = substr($sec, 0, $pos);
    					$list[] = $username;
    				}
    				$idx++;
    			}
    		}
    	}
    	return $list;
    }

    /**
     * Checks whether a user is the administrator of an access-path.
     * @param string $username
     * @param string $accesspath
     * @return bool
     */
    public function isUserAdminOfPath($username, $accesspath)
    {
      $paths = self::getAccessPathsOfUser($username);
      $pathsCount = count($paths);

      for ($i=0; $i<$pathsCount; $i++)
      {
        // I use this type of check, that also sub-paths are included.
        if (strpos($accesspath, $paths[$i]->path) === 0)
        {
          return true;
        }
      }

      return false;
    }

    /**
     * Checks whether a user is already directly assigned to an accesspath.
     * @param string $username
     * @param string $accesspath
     * @return bool
     */
    public function isUserAssignedToPath($username, $accesspath)
    {
      $section = $username.$this->path_postfix;
      if (!isset($this->assignments[$section]))
        return false;

      foreach ($this->assignments[$section] as $idx => &$path)
      {
        if ($path == $accesspath)
          return true;
      }
      return false;
    }

    /**
     * Filters out all access path on whih the user has admin privileges.
     * Returns a list of managable paths.
     * @param string $username
     * @param array<AccessPath> $fullList
     * @return array<AccessPath>
     */
    public function filterAccessPathsList($username, $fullList)
    {
      $list = array();
      foreach ($fullList as &$pathObj)
      {
        if (self::isUserAdminOfPath($username, $pathObj->path))
        {
          $list[] = $pathObj;
        }
      }
      return $list;
    }

    /**
     * Sets the project administrator for an access-path.
     * @param string $accesspath
     * @param string $username
     * @return bool Always returns TRUE.
     */
    public function assignAccessPathAdmin($accesspath, $username)
    {
      $section = $username.$this->path_postfix;
      if (!isset($this->assignments[$section]))
        $this->assignments[$section] = array();

      if (self::isUserAssignedToPath($username, $accesspath))
        return true;

      // Find the next index.
      $idx = count($this->assignments[$section]);

      // Add the access path to the list of managed paths of the user.
      $this->assignments[$section][$idx] = $accesspath;
      return true;
    }

    /**
     * Removes the project administrator of an access-path.
     * @param string $accesspath
     * @param string $username
     * @return bool Always returns TRUE.
     */
    public function removeAccessPathAdmin($accesspath, $username)
    {
      $section = $username.$this->path_postfix;
      if (!isset($this->assignments[$section]))
        return true; // User was not a project-manager.

      // Rebuild the list but skip the "to-remove" access-path.
      $list = array();
      foreach ($this->assignments[$section] as $idx => $path)
      {
        if ($path == $accesspath)
          continue; // Skip.
        $list[] = $path;
      }

      // Remove the $section from config and write it new.
      unset($this->assignments[$section]);

      $listCount = count($list);
      if ($listCount > 0)
      {
        $this->assignments[$section] = array();
        for ($i=0; $i<$listCount; $i++)
        {
          $this->assignments[$section][$i] = $list[$i];
        }
      }
      
      return true;
    }

    /**
     * Removes all access-path's assignments of the user.
     * @param <type> $username
     * @return bool Returns TRUE, if all access-path capabilities have been removed.
     *              Returns FALSE, if the user had no access-path definition.
     */
    public function deleteAccessPathAdmin($username)
    {
      $section = $username.$this->path_postfix;
      if (!isset($this->assignments[$section]))
        return false;

      unset($this->assignments[$section]);
      return true;
    }

    /**
     * Removes all assignments to an access-path.
     * @param string $accesspath
     * @return bool Always returns TRUE.
     */
    public function removeAssignmentsToPath($accesspath)
    {
      // First we collect the names of all prj-admins.
      $prjAdminList = array();
      foreach ($this->assignments as $sec => &$values)
      {
        if (strpos($sec, $this->path_postfix) !== false)
        {
          // Extract username from section name.
          $username = str_replace($this->path_postfix, "", $sec);
          $prjAdminList[] = $username;
        }
      }

      // Iterate all project admins and delete the assigned accesspath.
      foreach ($prjAdminList as $username)
      {
        self::removeAccessPathAdmin($accesspath, $username);
      }

      return true;
    }

    /**
     * Saves the assignments to file.
     * @return bool
     */
    public function save()
    {
      if ($this->assignments !== NULL)
      {
        if (if_write_ini_file($this->user_role_file, $this->assignments))
        {
          return true;
        }
      }
      return false;
    }

    /**
     * Loads the default ACL's and parses the ACL-to-User assignment file.
     * @return bool
     * @throws Exception If the user-to-role file can not been created.
     */
    public function load()
    {
    	// Create the user-to-role file.
    	if (!file_exists($this->user_role_file))
    	{
    		// Create the file.
    		if (!touch($this->user_role_file))
    		{
    		  throw new Exception("The file is not writable: ".$this->user_role_file);
    		}
    	}
    	
      // Load the default ACL object.
      $this->acl = self::getDefaultAcl();

      // User<>Role assignment file.
      $this->assignments = if_parse_ini_file($this->user_role_file);
    }

    /**
     * Creates a default ACL object and returns it.
     * @return IF_ACL
     */
    private function getDefaultAcl()
    {
      $o = new \IF_ACL();
      $o->addModule(new \IF_ACLModule(\ACL_MOD_BASIC));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_REPO));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_USER));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_GROUP));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_ACCESSPATH));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_ROLE));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_UPDATE));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_SETTINGS));
      $o->addModule(new \IF_ACLModule(\ACL_MOD_PROJECTMANAGER));

      // Basic user (Default role)
      $n = "User";
      $o->addRole(new \IF_ACLRole($n, "Can login and change the own password."));
      $o->addRule($n, \ACL_MOD_BASIC, array(\ACL_ACTION_LOGIN));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_CHANGEPASS));

      // User-Group-Administrator (inhertis "User")
      $n = "User-Group-Manager";
      $o->addRole(new \IF_ACLRole($n, "Create/Delete users and groups and manages memberships and change passwords of users."), array("User"));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_ADD));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_DELETE));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_CHANGEPASS_OTHER));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_ADD));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_DELETE));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_ASSIGN));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_UNASSIGN));

      // Access-Path-Manager
      $n = "Access-Path-Manager";
      $o->addRole(new \IF_ACLRole($n, "Create/Delete Access-Paths and manages user and group permissions."), array("User"));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_GROUP, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_ADD));
      $o->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_DELETE));
      $o->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_ASSIGN));
      $o->addRule($n, \ACL_MOD_ACCESSPATH, array(\ACL_ACTION_UNASSIGN));
      $o->addRule($n, \ACL_MOD_REPO, array(\ACL_ACTION_VIEW));

      // Repository-Creator
      $n = "Repository-Creator";
      $o->addRole(new \IF_ACLRole($n, "Can create new repositories, but NOT delete."), array("User"));
      $o->addRule($n, \ACL_MOD_REPO, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_REPO, array(\ACL_ACTION_ADD));

      // Repository-Manager
      $n = "Repository-Manager";
      $o->addRole(new \IF_ACLRole($n, "Create/Delete repositories."), array("User","Repository-Creator"));
      $o->addRule($n, \ACL_MOD_REPO, array(\ACL_ACTION_DELETE));

      // Role-Manager
      $n = "Role-Manager";
      $o->addRole(new \IF_ACLRole($n, "Assign and unassign web application roles to users."), array("User"));
      $o->addRule($n, \ACL_MOD_USER, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_ROLE, array(\ACL_ACTION_VIEW));
      $o->addRule($n, \ACL_MOD_ROLE, array(\ACL_ACTION_ASSIGN));
      $o->addRule($n, \ACL_MOD_ROLE, array(\ACL_ACTION_UNASSIGN));

      // Update-Manager
      $n = "Update-Manager";
      $o->addRole(new \IF_ACLRole($n, "Can synchronize the user data from provider with the SVNAuthFile."), array("User"));
      $o->addRule($n, \ACL_MOD_UPDATE, array(\ACL_ACTION_SYNCHRONIZE));

      // Administrator role
      // The administrator can do everything!
      $n = $this->administrator_role_name;
      $o->addRole(new \IF_ACLRole($n, "Web application administrator."));
      $o->addRule($n);
      return $o;
    }
  } // Class
} // Namespace
?>