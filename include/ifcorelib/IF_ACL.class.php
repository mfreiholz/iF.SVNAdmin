<?php

class IF_ACL_Exception extends Exception
{
  public function __construct($message = "", $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}

class IF_ACL
{
  // Holds all modules as assoc-array.
  // "module_name" => IF_ACLModule
  private $modules = array();

  // Holds all roles as assoc-array.
  // "role_name" => IF_ACLRole
  private $roles = array();

  // Holds all defined rules for the modules and roles.
  // ...
  private $rules = null;


  public function __construct()
  {
    // Create the default rules.
    // "all" use for "Administrator" role,
    // when "all" value is "*", means add can do everything.
    // "modules" save the different roles for different modules.
    $this->rules = array("all" => null,
      "modules" => array());
  }

  /**
   * Adds a new module to the ACL.
   *
   * @param IF_ACLModule $module The new module.
   */
  public function addModule(IF_ACLModule $module)
  {
    $this->modules[$module->getName()] = $module;
  }

  /**
   * Adds a new role to the ACL.
   *
   * @param IF_ACLRole $role The new role.
   */
  public function addRole(IF_ACLRole $role, array $parents = null)
  {
    // Add the role to the list of roles.
    $this->roles[$role->getName()] = array("object" => $role,
      "parents" => null,
      "childs" => null);
    // treatment of parents and childs.
    // Behandlung der parents und childs.
    if ($parents !== null && count($parents) > 0) {
      $parentObjectArray = array();

      // Da parents gegeben sind, muss die Rolle nun bei allen Parents
      // als Child eingehängt werden.
      foreach ($parents as $name) {
        // Holen des parent Role Objektes.
        // save parents roles to parent object
        $parentRole = self::getRoleByName($name);
        array_push($parentObjectArray, $parentRole);

        // Anhängen der Rolle an einen bestehenden Parent.
        $childs = &$this->roles[$name]["childs"];
        if ($childs == null)
          $childs = array();
        array_push($childs, $role);
      }

      // add parents list to roles object.
      // roles will display the role name and the parents role information
      // such as:
      // 'User-Group-Manager' 'parents' => [ 'User' ]
      // 'Access-Path-Manager' 'parents' => [ 'User' ]
      // 'Repository-Creator' 'parents' => [ 'User' ]
      // 'Repository-Manager' 'parents' => [ 'User', 'Repository-Creator' ]
      // 'Role-Manager' 'parents' => [ 'User' ]
      // 'Update-Manager' 'parents' => [ 'User' ]
      //
      // Nun kann die Liste der Parents eingehängt werden.
      $this->roles[$role->getName()]["parents"] = $parentObjectArray;
    }
  }

  /**
   * Adds a new rule to the ACL.
   *
   * @param string $role
   * @param string $module
   * @param array $actions
   */
  public function addRule($role, $module = null, array $actions = array())
  {
    // Role exists?
    if (!$this->roleExists($role)) {
      throw new IF_ACL_Exception("The role $role doesn't exist.");
    }

    // Permissions for a specific module.
    // check the permission of the module
    if ($module !== null) {
      if (!$this->moduleExists($module)) {
        throw new IF_ACL_Exception("The module $module doesn't exist.");
      }

      // If the role have permissions to ALL modules, we remove them now.
      // Administrator can do everything, no limits.
      if (isset($this->rules["all"][$role])) {
        unset($this->rules["all"][$role]);
      }

      // Are there actions defined?
      // if no defined actions. then give Administrator role
      if (count($actions) < 1) {
        // Grant all.
        if_log_debug('If no action defined, grant all permissions');
        $this->rules["modules"][$module]["roles"][$role] = array();
      } else {
        if_log_debug('defined the actions, checking...');

        // Grant specific actions + the old ones.
        if (isset($this->rules["modules"][$module]["roles"][$role])) {
          if_log_debug('the role has rules, then add this one to the old rules list');
          // Merge the old with the new modules.
          $currentActions = $this->rules["modules"][$module]["roles"][$role];
          // Finally, the $this->rules["modules"][$module]["roles"][$role] like this:
          // array('view', 'add', 'delete', 'assign')
          $this->rules["modules"][$module]["roles"][$role] = array_unique(array_merge($currentActions, $actions));

        } else {
          // No old actions defined. Set the new actions.
          // add action, such as: 'login'/'changepass'/'view'/'delete'/'synchronize'/'add'/'assign'/'changepassother'/'unassign'
          $this->rules["modules"][$module]["roles"][$role] = $actions;
        }
      }
    }
    // No specific module was specified,
    // so rights are granted across all modules.

    // Es wurde kein spezifisches Modul angegeben,
    // also werden Rechte über alle Module erteilt.
    else {
      if_log_debug('user inputed the $module string.');

      // Do other rights already exist for the role?
      // If so, then these must now be deleted.
      // For this purpose, all modules are now iterated.

      // Existieren schon andere Rechte für die Rolle?
      // Wenn ja, dann müssen diese jetzt gelöscht werden.
      // Dazu werden jetzt alle Module iteriert.
      if (!empty($this->rules["modules"])) {
        foreach ($this->rules["modules"] as $mod => &$v) {
          // $mod may be the string value like this:
          // 'basics'/'repositories'/'users'/'groups'/'accesspaths'/'roles'/'updates'/'settings'/'projectmanagers'
          if (isset($this->rules["modules"][$mod]["roles"][$role]))
            unset($this->rules["modules"][$mod]["roles"][$role]);
        }
      }

      // Assign ALL rights.
      // "*" means all rights, Administrator have all rights.
      // $this->rules["modules"] save other roles permission.
      // The value here has nothing to do with whether the user is assigned
      // the administrator role or other roles, only the role is relevant.
      $this->rules["all"][$role] = "*";
    }
  }

  /**
   * Checks whether the given role has access to the requested module
   * with the given action.
   * @param string $role
   * @param string $module
   * @param string $action
   * @return bool
   */
  public function hasPermission($role, $module, $action)
  {
    if_log_debug("Checks whether the given role has access to the requested module with the given action.");

    // Check #1
    // Does the role have rights to all modules?
    // Hat die Rolle Rechte auf alle Module?
    if (isset($this->rules["all"][$role])) {
      if_log_debug('the role is Administrator, can do everything!');
      return true;
    }

    // Check #2
    // Does the role have general rights or the requested right to the module?
    // Hat die Rolle generelle Rechte oder das angefordete Recht auf das Modul?
    if (isset($this->rules["modules"][$module])) {
      if (isset($this->rules["modules"][$module]["roles"][$role])) {
        // $rights will will save all the roles permission for this module.
        // for example. Access-Path-Manager page 'Access-Path-Manager' can do 'view'/'add'/'delete'/'assign'/'unassign' actions.
        $rights = &$this->rules["modules"][$module]["roles"][$role];
        if (count($rights) == 0)
          // the role is Administrator, have all the premissions.
          return true; // Die Rolle hat alle Rechte auf das Modul.
        else if (in_array($action, $rights))
          // the role have special premission for this module
          return true; // Die Rolle hat das spezifische Recht auf das Modul.
      }
    }
    //else
    //throw new IF_ACL_Exception("The module $module doesn't have any rules defined.");

    // Check #3
    // Does one of the parent roles have the necessary rights on the module?
    // All parents iterate until the right right is found.
    if (isset($this->roles[$role]["parents"])) {
      $parentObjects = &$this->roles[$role]["parents"];
      if ($parentObjects != null) {
        foreach ($parentObjects as &$p) {
          if (self::hasPermission($p->getName(), $module, $action))
            return true;
          else
            continue;
        }
      }
    }

    return false;
  }

  /*****************************************************************************
   * Helper functions.
   ****************************************************************************/

  /**
   * Checks whether a role with the given name exists.
   *
   * @param string $role Name of the role.
   * such as:'User'/'User-Group-Manager'/'Access-Path-Manager'/'Repository-Creator'/'Repository-Manager'/'Role-Manager'/'Update-Manager'/'Administrator'
   * @return bool
   */
  public function roleExists($role)
  {
    return isset($this->roles[$role]);
  }

  /**
   * Checks whether a module with the given name exists.
   *
   * @param string $module Name of the module.
   * such as:'basics'/'repositories'/'users'/'groups'/'accesspaths'/'roles'/'updates'/'settings'/'projectmanagers'
   * @return bool
   */
  public function moduleExists($module)
  {
    if_log_debug('check whether a module with the given name exists.');
    return isset($this->modules[$module]);
  }

  /**
   * Gets the existing role object by its name.
   * @param string $name
   * @return IF_ACLRole Reference to the role object.
   */
  public function getRoleByName($name)
  {
    if_log_debug('Gets the existing role object by its name.');
    if_log_array($this->roles[$name]["object"]);
    return $this->roles[$name]["object"];
  }

  /*****************************************************************************
   * Some public functions to receive containing data.
   ****************************************************************************/

  /**
   * Gets all role objects.
   * @return array<IF_ACLRole>
   */
  public function getRoles()
  {
    $ret = array();
    foreach ($this->roles as $role_name => &$val) {
      array_push($ret, $val["object"]);
    }
    return $ret;
  }
}

?>