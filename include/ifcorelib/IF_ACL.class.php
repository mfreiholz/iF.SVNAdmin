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
  // 保存所有模型
  // "module_name" => IF_ACLModule
  private $modules = array();

  // Holds all roles as assoc-array.
  // 保存所有角色名称
  // "role_name" => IF_ACLRole
  private $roles = array();

  // Holds all defined rules for the modules and roles.
  // 保存所有定义的规则
  // ...
  private $rules = null;


  public function __construct()
  {
    // Create the default rules.
    // 创建默认规则,默认规则为空
    // "all" 用于"Administrator"角色,当其值是"*"时,表明Administrator具备一切权限
    // "modules"用于存储各个模型被分配了哪些角色,属于这些角色的用户就可以访问该模型
    $this->rules = array( "all" => null,
                          "modules" => array() );
  }

  /**
   * Adds a new module to the ACL.
   * 添加新的模型到ACL中
   *
   * @param IF_ACLModule $module The new module.
   */
  public function addModule(IF_ACLModule $module)
  {
    // 朝所有模型列表中增加元素
    $this->modules[$module->getName()] = $module;
  }

  /**
   * Adds a new role to the ACL.
   * 增加新的角色到ACL中
   *
   * @param IF_ACLRole $role The new role.
   */
  public function addRole(IF_ACLRole $role, array $parents=null)
  {
    // Add the role to the list of roles.
    // 添加到角色列表中,索引是角色的名称,值是一个列表,列表中包含角色对象,父节点,子节点等
    $this->roles[$role->getName()] = array( "object" => $role,
                                            "parents" => null,
                                            "childs" => null );
    // treatment of parents and childs.
    // 处理父母和子节点,也就是考虑继承关系
    // Behandlung der parents und childs.
    // 如果父列表不为空,那么对列表进行处理
    if ($parents !== null && count($parents) > 0)
    {
      $parentObjectArray = array();

      // Da parents gegeben sind, muss die Rolle nun bei allen Parents
      // als Child eingehängt werden.
      // 父角色处理
      foreach ($parents as $name)
      {
        // Holen des parent Role Objektes.
        // 将父节点的角色都保存到父对象中
        $parentRole = self::getRoleByName($name);
        array_push($parentObjectArray, $parentRole);

        // Anhängen der Rolle an einen bestehenden Parent.
        $childs = &$this->roles[$name]["childs"];
        if ($childs == null)
          $childs = array();
        array_push($childs, $role);
      }

      // 将父母列表附加到roles对象里面去
      // 这样roles会展示出角色和角色父角色信息
      // 如:
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
   * 添加规则到ACL中
   *
   * @param string $role 角色
   * @param string $module 模型
   * @param array $actions 动作
   */
  public function addRule($role, $module=null, array $actions=array())
  {
    // Role exists?
    // 首先判断角色是否存在
    if (!$this->roleExists($role))
    {
      throw new IF_ACL_Exception("The role $role doesn't exist.");
    }

    // Permissions for a specific module.
    // 判断模型的权限
    if ($module !== null)
    {
      // 如果模型不存在,那么抛出异常
      if (!$this->moduleExists($module))
      {
        throw new IF_ACL_Exception("The module $module doesn't exist.");
      }

      // If the role have permissions to ALL modules, we remove them now.
      // 如果角色有所有模型的权限,将其移除,如'Administrator'管理员角色可以做任何事情,没有任何限制
      if (isset($this->rules["all"][$role]))
      {
        unset($this->rules["all"][$role]);
      }

      // Are there actions defined?
      // 如果没有定义动作,则给予所的权限,也就是'Administrator'管理员角色
      if (count($actions) < 1)
      {
        // Grant all.
        if_log_debug('If no action defined, grant all permissions');
        $this->rules["modules"][$module]["roles"][$role] = array();
      }
      else
      {
        if_log_debug('defined the actions, checking...');

        // Grant specific actions + the old ones.
        if (isset($this->rules["modules"][$module]["roles"][$role]))
        {
          if_log_debug('the role has rules, then add this one to the old rules list');
          // Merge the old with the new modules.
          $currentActions = $this->rules["modules"][$module]["roles"][$role];
          // Finally, the $this->rules["modules"][$module]["roles"][$role] like this:
          // array('view', 'add', 'delete', 'assign')
          $this->rules["modules"][$module]["roles"][$role] = array_unique(array_merge($currentActions, $actions));

        }
        else
        {
          // No old actions defined. Set the new actions.
          // 如果模型中不有定义该规则,则把该规则加入到模型中
          // 添加action动作,如: 'login'/'changepass'/'view'/'delete'/'synchronize'/'add'/'assign'/'changepassother'/'unassign'等
          $this->rules["modules"][$module]["roles"][$role] = $actions;
        }
      }
    }
    // No specific module was specified,
    // so rights are granted across all modules.
    // 没有指定模型时,将应用于所有模板授权
    // Es wurde kein spezifisches Modul angegeben,
    // also werden Rechte über alle Module erteilt.
    else
    {
      if_log_debug('user inputed the $module string.');

      // Do other rights already exist for the role?
      // If so, then these must now be deleted.
      // For this purpose, all modules are now iterated.

      //该角色是否已经存在其他权利？
      //如果是这样，则必须立即将其删除。
      //现在，所有模块都已迭代。

      // Existieren schon andere Rechte für die Rolle?
      // Wenn ja, dann müssen diese jetzt gelöscht werden.
      // Dazu werden jetzt alle Module iteriert.
      if (!empty($this->rules["modules"]))
      {
        foreach ($this->rules["modules"] as $mod => &$v)
        {
          // $mod may be the string value like this:
          // 'basics'/'repositories'/'users'/'groups'/'accesspaths'/'roles'/'updates'/'settings'/'projectmanagers'
          if (isset($this->rules["modules"][$mod]["roles"][$role]))
            unset($this->rules["modules"][$mod]["roles"][$role]);
        }
      }

      // Assign ALL rights.
      // 分配所有权限。用"*"星号代表所有权限,管理员拥有所有权限
      // $this->rules["modules"] 存储其他角色的权限
      // 此处的值与用户是否分配了管理员角色或其他角色没有关系,只与角色相关
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
    if_log_debug("Checks whether the given role has access to the requested module with the given action.");

    // Check #1
    // Does the role have rights to all modules?
    // Hat die Rolle Rechte auf alle Module?
    // 角色是否对所有模块都有权限？也就是检查当前用户是否有管理员权限,有的话任何模块都能操作
    if (isset($this->rules["all"][$role]))
    {
      if_log_debug('the role is Administrator, can do everything!');
      return true;
    }

    // Check #2
    // Does the role have general rights or the requested right to the module?
    // Hat die Rolle generelle Rechte oder das angefordete Recht auf das Modul?
    // 角色对模块具有一般权利还是所要求的权利？
    if (isset($this->rules["modules"][$module]))
    {
      if (isset($this->rules["modules"][$module]["roles"][$role]))
      {
        // $rights记录每个角色针对该模型可以拥有的动作,
        //如'访问路径管理'页面,'Access-Path-Manager'角色可以拥有'view'/'add'/'delete'/'assign'/'unassign'等动作
        $rights = &$this->rules["modules"][$module]["roles"][$role];
        if (count($rights) == 0)
          return true; // Die Rolle hat alle Rechte auf das Modul. 该角色对该模块拥有所有权利。
        else if (in_array($action, $rights))
          return true; // Die Rolle hat das spezifische Recht auf das Modul.该角色对模块具有特定的权利。
      }
    }
    //else
      //throw new IF_ACL_Exception("The module $module doesn't have any rules defined.");

    // Check #3
    // Does one of the parent roles have the necessary rights on the module?
    // All parents iterate until the right right is found.

    // Hat eine der parent Rollen das nötige Recht auf dem Modul?
    // Alle parents iterieren bis das passende Recht gefunden wurde.

    //父角色之一是否对模块具有必要的权限？
    //所有父母进行迭代，直到找到正确的权利为止。
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
   * 检查角色名称对象的角色是否存在
   * @param string $role Name of the role. 角色名称字符串
   * 如:'User'/'User-Group-Manager'/'Access-Path-Manager'/'Repository-Creator'/'Repository-Manager'/'Role-Manager'/'Update-Manager'/'Administrator'等
   * @return bool
   */
  public function roleExists($role)
  {
    return isset($this->roles[$role]);
  }

  /**
   * Checks whether a module with the given name exists.
   * 检查指定模板是否存在
   * @param string $module Name of the module. 模板名称
   * 如:'basics'/'repositories'/'users'/'groups'/'accesspaths'/'roles'/'updates'/'settings'/'projectmanagers'等
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
    // 通过角色的名称，获取存在的角色对象
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
    foreach ($this->roles as $role_name => &$val)
    {
      array_push($ret, $val["object"]);
    }
    return $ret;
  }
}
?>