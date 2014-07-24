<?php
class AclBase {
  /**
   * Holds all modules as assoc-array.
   * module-id => AclModule
   *
   * @var array<string,AclModule>
   */
  private $modules = array ();

  /**
   * Holds all roles as assoc-array.
   * role-id => AclRole
   *
   * @var array<string,AclRole>
   */
  private $roles = array ();

  /**
   *
   * @var array
   */
  private $rules = null;

  public function __construct() {
    // Create the default rules.
    $this->rules = array (
        "all" => null,
        "modules" => array ()
    );
  }

  /**
   * Adds a new module to the ACL.
   *
   * @param AclModule $module
   *          The new module.
   */
  public function addModule(AclModule $module) {
    $this->modules[$module->getId()] = $module;
  }

  /**
   * Adds a new role to the ACL.
   * If the role does have $parents, it will inherit the permissions of them.
   *
   * @param AclRole $role
   * @param array $parents
   */
  public function addRole(AclRole $role, array $parents = null) {
    // Add the role to the list of roles.
    $this->roles[$role->getId()] = array (
        "object" => $role,
        "parents" => null,
        "childs" => null
    );

    // Behandlung der parents und childs.
    if ($parents !== null && count($parents) > 0) {
      $parentObjectArray = array ();

      // Da parents gegeben sind, muss die Rolle nun bei allen Parents
      // als Child eingehängt werden.
      foreach ($parents as $id) {
        // Holen des parent Role Objektes.
        $parentRole = self::getRoleById($id);
        array_push($parentObjectArray, $parentRole);

        // Anhängen der Rolle an einen bestehenden Parent.
        $childs = &$this->roles[$id]["childs"];
        if ($childs == null)
          $childs = array ();
        array_push($childs, $role);
      }

      // Nun kann die Liste der Parents eingehängt werden.
      $this->roles[$role->getId()]["parents"] = $parentObjectArray;
    }
  }

  /**
   * Adds a new rule to the ACL.
   *
   * @param string $roleId
   * @param string $moduleId
   * @param array $actions
   */
  public function addRule($roleId, $moduleId = null, array $actions = array()) {
    if (!$this->roleExists($roleId)) {
      // The role doesn't exists.
      return false;
    }

    // Permissions for a specific module.
    if ($moduleId !== null) {
      if (!$this->moduleExists($moduleId)) {
        // The module doesn't exists.
        return false;
      }

      // If the role have permissions to ALL modules, we remove them now.
      if (isset($this->rules["all"][$roleId])) {
        unset($this->rules["all"][$roleId]);
      }

      // Are there actions defined?
      if (count($actions) < 1) {
        // Grant all.
        $this->rules["modules"][$moduleId]["roles"][$roleId] = array ();
      } else {
        // Grant specific actions + the old ones.
        if (isset($this->rules["modules"][$moduleId]["roles"][$roleId])) {
          // Merge the old with the new modules.
          $currentActions = $this->rules["modules"][$moduleId]["roles"][$roleId];
          $this->rules["modules"][$moduleId]["roles"][$roleId] = array_unique(array_merge($currentActions, $actions));
        } else {
          // No old actions defined. Set the new actions.
          $this->rules["modules"][$moduleId]["roles"][$roleId] = $actions;
        }
      }
    }     // Es wurde kein spezifisches Modul angegeben,
      // also werden Rechte über alle Module erteilt.
    else {
      // Existieren schon andere Rechte für die Rolle?
      // Wenn ja, dann müssen diese jetzt gelöscht werden.
      // Dazu werden jetzt alle Module iteriert.
      if (!empty($this->rules["modules"])) {
        foreach ($this->rules["modules"] as $mod => &$v) {
          if (isset($this->rules["modules"][$mod]["roles"][$roleId]))
            unset($this->rules["modules"][$mod]["roles"][$roleId]);
        }
      }

      // Zuweisen der ALL Rechte.
      $this->rules["all"][$roleId] = "*";
    }
  }

  /**
   * Checks whether the given role has access to the requested module
   * with the given action.
   *
   * @param string $role
   * @param string $module
   * @param string $action
   * @return bool
   */
  public function hasPermission($roleId, $moduleId, $action) {
    // Check #1
    // Hat die Rolle Rechte auf alle Module?
    if (isset($this->rules["all"][$roleId]))
      return true;

      // Check #2
      // Hat die Rolle generelle Rechte oder das angefordete Recht auf das Modul?
    if (isset($this->rules["modules"][$moduleId])) {
      if (isset($this->rules["modules"][$moduleId]["roles"][$roleId])) {
        $rights = &$this->rules["modules"][$moduleId]["roles"][$roleId];
        if (count($rights) == 0)
          return true; // Die Rolle hat alle Rechte auf das Modul.
        else if (in_array($action, $rights))
          return true; // Die Rolle hat das spezifische Recht auf das Modul.
      }
    }
    // else
    // throw new AclBase_Exception("The module $module doesn't have any rules defined.");

    // Check #3
    // Hat eine der parent Rollen das nötige Recht auf dem Modul?
    // Alle parents iterieren bis das passende Recht gefunden wurde.
    if (isset($this->roles[$roleId]["parents"])) {
      $parentObjects = &$this->roles[$roleId]["parents"];
      if ($parentObjects != null) {
        foreach ($parentObjects as &$p) {
          if (self::hasPermission($p->getId(), $moduleId, $action))
            return true;
          else
            continue;
        }
      }
    }

    return false;
  }

  public function roleExists($roleId) {
    return isset($this->roles[$roleId]);
  }

  public function moduleExists($moduleId) {
    return isset($this->modules[$moduleId]);
  }

  public function getRoleById($roleId) {
    return $this->roles[$roleId]["object"];
  }

  public function getRoles() {
    $ret = array ();
    foreach ($this->roles as $roleId => &$val) {
      array_push($ret, $val["object"]);
    }
    return $ret;
  }

}
?>