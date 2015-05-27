<?php
class IF_ACL_Exception extends Exception
{
  public function __construct($message="", $code=0, Exception $previous=null)
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
    $this->rules = array( "all" => null,
                          "modules" => array() );
  }

  /**
   * Adds a new module to the ACL.
   * @param IF_ACLModule $module The new module.
   */
  public function addModule(IF_ACLModule $module)
  {
    $this->modules[$module->getName()] = $module;
  }

  /**
   * Adds a new role to the ACL.
   * @param IF_ACLRole $role The new role.
   */
  public function addRole(IF_ACLRole $role, array $parents=null)
  {
    // Add the role to the list of roles.
    $this->roles[$role->getName()] = array( "object" => $role,
                                            "parents" => null,
                                            "childs" => null );

    // Behandlung der parents und childs.
    if ($parents !== null && count($parents) > 0)
    {
      $parentObjectArray = array();

      // Da parents gegeben sind, muss die Rolle nun bei allen Parents
      // als Child eingehängt werden.
      foreach ($parents as $name)
      {
        // Holen des parent Role Objektes.
        $parentRole = self::getRoleByName($name);
        array_push($parentObjectArray, $parentRole);

        // Anhängen der Rolle an einen bestehenden Parent.
        $childs = &$this->roles[$name]["childs"];
        if ($childs == null)
          $childs = array();
        array_push($childs, $role);
      }

      // Nun kann die Liste der Parents eingehängt werden.
      $this->roles[$role->getName()]["parents"] = $parentObjectArray;
    }
  }

  /**
   * Adds a new rule to the ACL.
   * @param string $role
   * @param string $module
   * @param array $actions
   */
  public function addRule($role, $module=null, array $actions=array())
  {
    // Role exists?
    if (!$this->roleExists($role))
    {
      throw new IF_ACL_Exception("The role $role doesn't exist.");
    }

    // Permissions for a specific module.
    if ($module !== null)
    {
      if (!$this->moduleExists($module))
      {
        throw new IF_ACL_Exception("The module $module doesn't exist.");
      }

      // If the role have permissions to ALL modules, we remove them now.
      if (isset($this->rules["all"][$role]))
      {
        unset($this->rules["all"][$role]);
      }

      // Are there actions defined?
      if (count($actions) < 1)
      {
        // Grant all.
        $this->rules["modules"][$module]["roles"][$role] = array();
      }
      else
      {
        // Grant specific actions + the old ones.
        if (isset($this->rules["modules"][$module]["roles"][$role]))
        {
          // Merge the old with the new modules.
          $currentActions = $this->rules["modules"][$module]["roles"][$role];
          $this->rules["modules"][$module]["roles"][$role] = array_unique(array_merge($currentActions, $actions));
        }
        else
        {
          // No old actions defined. Set the new actions.
          $this->rules["modules"][$module]["roles"][$role] = $actions;
        }
      }
    }
    // Es wurde kein spezifisches Modul angegeben,
    // also werden Rechte über alle Module erteilt.
    else
    {
      // Existieren schon andere Rechte für die Rolle?
      // Wenn ja, dann müssen diese jetzt gelöscht werden.
      // Dazu werden jetzt alle Module iteriert.
      if (!empty($this->rules["modules"]))
      {
        foreach ($this->rules["modules"] as $mod => &$v)
        {
          if (isset($this->rules["modules"][$mod]["roles"][$role]))
            unset($this->rules["modules"][$mod]["roles"][$role]);
        }
      }

      // Zuweisen der ALL Rechte.
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
    // Check #1
    // Hat die Rolle Rechte auf alle Module?
    if (isset($this->rules["all"][$role]))
      return true;

    // Check #2
    // Hat die Rolle generelle Rechte oder das angefordete Recht auf das Modul?
    if (isset($this->rules["modules"][$module]))
    {
      if (isset($this->rules["modules"][$module]["roles"][$role]))
      {
        $rights = &$this->rules["modules"][$module]["roles"][$role];
        if (count($rights) == 0)
          return true; // Die Rolle hat alle Rechte auf das Modul.
        else if (in_array($action, $rights))
          return true; // Die Rolle hat das spezifische Recht auf das Modul.
      }
    }
    //else
      //throw new IF_ACL_Exception("The module $module doesn't have any rules defined.");

    // Check #3
    // Hat eine der parent Rollen das nötige Recht auf dem Modul?
    // Alle parents iterieren bis das passende Recht gefunden wurde.
    if (isset($this->roles[$role]["parents"]))
    {
      $parentObjects = &$this->roles[$role]["parents"];
      if ($parentObjects != null)
      {
        foreach ($parentObjects as &$p)
        {
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
   * @param string $role Name of the role.
   * @return bool
   */
  public function roleExists($role)
  {
    return isset($this->roles[$role]);
  }

  /**
   * Checks whether a module with the given name exists.
   * @param string $module Name of the module.
   * @return bool
   */
  public function moduleExists($module)
  {
    return isset($this->modules[$module]);
  }

  /**
   * Gets the existing role object by its name.
   * @param string $name
   * @return IF_ACLRole Reference to the role object.
   */
  public function getRoleByName($name)
  {
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
    foreach ($this->roles as $role_name => &$val)
    {
      array_push($ret, $val["object"]);
    }
    return $ret;
  }
}
?>