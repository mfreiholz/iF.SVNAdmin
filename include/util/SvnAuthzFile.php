<?php
/**
 */
class SvnAuthzFileAlias {
  public $alias = "";
  public $value = "";

  public function asMemberString() {
    return "&" . $this->alias;
  }

}

/**
 */
class SvnAuthzFileGroup {
  public $name = "";

  public function asMemberString() {
    return "@" . $this->name;
  }

}

/**
 */
class SvnAuthzFileUser {
  public $name = "";

  public function asMemberString() {
    return $this->name;
  }

}

/**
 */
class SvnAuthzFilePath {
  public $repository = "";
  public $path = "";

  public function asString() {
    $s = "";
    if (!empty($this->repository)) {
      $s .= $this->repository;
      $s .= ":";
    }
    $s .= $this->path;
    return $s;
  }

}

/**
 */
class SvnAuthzFilePermission {
  public $member = null;
  public $permission = "";

}

/**
 */
class SvnAuthzFile {
  // Permissions.
  const PERM_NONE = "";
  const PERM_READ = "r";
  const PERM_READWRITE = "rw";

  // Special sections and placeholders.
  const GROUP_SECTION = "groups";
  const GROUP_SIGN = "@";
  const ALIAS_SECTION = "aliases";
  const ALIAS_SIGN = "&";
  const ALL_USERS_SIGN = "*";

  // Errors.
  const NO_ERROR = 0;
  const UNKNOWN_ERROR = 1;
  const FILE_ERROR = 2;

  // Attributes.
  private $_errorString = "";
  private $_ini = null;

  public function __construct() {
  }

  public function loadFromFile($path) {
    $ini = new IniFile();
    if ($ini->loadFromFile($path) !== IniFile::NO_ERROR) {
      return SvnAuthzFile::FILE_ERROR;
    }
    $this->_ini = $ini;
    return SvnAuthzFile::NO_ERROR;
  }

  public function writeToFile($path = null) {
    if (!$this->_ini) {
      return SvnAuthzFile::UNKNOWN_ERROR;
    }
    if ($this->_ini->writeToFile($path) !== IniFile::NO_ERROR) {
      return SvnAuthzFile::FILE_ERROR;
    }
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   *
   * @return array<SvnAuthzFileAlias>
   */
  public function getAliases() {
    $aliases = array ();
    $section = $this->_ini->getSection(SvnAuthzFile::ALIAS_SECTION);
    if ($section) {
      foreach ($section->items as &$item) {
        $alias = new SvnAuthzFileAlias();
        $alias->alias = $item->key;
        $alias->value = $item->value;
        $aliases[] = $alias;
      }
    }
    return $aliases;
  }

  /**
   *
   * @return array<SvnAuthzFileGroup>
   */
  public function getGroups() {
    $groups = array ();
    $section = $this->_ini->getSection(SvnAuthzFile::GROUP_SECTION);
    if ($section) {
      foreach ($section->items as &$item) {
        $group = new SvnAuthzFileGroup();
        $group->name = $item->key;
        $groups[] = $group;
      }
    }
    return $groups;
  }

  /**
   *
   * @return array<SvnAuthzFilePath>
   */
  public function getPaths() {
    $paths = array ();
    $sections = $this->_ini->getSections();
    foreach ($sections as &$section) {
      if ($section->name === SvnAuthzFile::ALIAS_SECTION || $section->name === SvnAuthzFile::GROUP_SECTION) {
        continue;
      }
      $path = new SvnAuthzFilePath();
      $pos = 0;
      if (($pos = strpos($section->name, ":/")) !== false) {
        $path->repository = substr($section->name, 0, $pos);
        $path->path = substr($section->name, $pos + 1);
      } else {
        $path->path = $section->name;
      }
      $paths[] = $path;
    }
    return $paths;
  }

  /**
   *
   * @param string $group
   * @return array<SvnAuthzFileAlias,SvnAuthzFileGroup,SvnAuthzFileUser>
   */
  public function getMembersOfGroup($group) {
    $members = array ();
    $value = $this->_ini->getValue(SvnAuthzFile::GROUP_SECTION, $group, "");
    if (empty($value)) {
      return $members;
    }
    $membersParts = explode(",", $value);
    foreach ($membersParts as &$member) {
      $obj = $this->createMemberObject($member);
      $members[] = $obj;
    }
    return $members;
  }

  /**
   *
   * @param SvnAuthzFilePath $path
   * @return array<SvnAuthzFileAlias,SvnAuthzFileGroup,SvnAuthzFileUser>
   */
  public function getPermissionsOfPath(SvnAuthzFilePath $path) {
    $perms = array ();
    $section = $this->_ini->getSection($path->asString());
    if ($section) {
      foreach ($section->items as &$item) {
        $perm = new SvnAuthzFilePermission();
        $perm->member = $this->createMemberObject($item->key);
        $perm->permission = $item->value;
        $perms[] = $perm;
      }
    }
    return $perms;
  }

  public function addAlias(SvnAuthzFileAlias $obj) {
    $this->_ini->setValue(SvnAuthzFile::ALIAS_SECTION, $obj->alias, $obj->value);
  }

  public function removeAlias(SvnAuthzFileAlias $obj) {
    $this->_ini->removeValue(SvnAuthzFile::ALIAS_SECTION, $obj->alias);
    // Remove from groups.
    // ...
    // Remove permissions.
    // ...
  }

  public function addGroup(SvnAuthzFileGroup $obj) {
    $exists = false;
    $section = $this->_ini->getSection(SvnAuthzFile::GROUP_SECTION);
    if ($section) {
      foreach ($section->items as &$item) {
        if ($item->key === $obj->name) {
          $exists = true;
          break;
        }
      }
    }
    if (!$exists) {
      $this->_ini->setValue(SvnAuthzFile::GROUP_SECTION, $obj->name, "");
    }
  }

  public function removeGroup(SvnAuthzFileGroup $obj) {
    $this->_ini->removeValue(SvnAuthzFile::GROUP_SECTION, $obj->name);
    // Remove from groups.
    // ...
    // Remove permissions.
    // ...
  }

  public function addMember(SvnAuthzFileGroup $group, $memberObj) {
    $section = $this->_ini->getSection(SvnAuthzFile::GROUP_SECTION);
    if ($section) {

    }
  }

  public function removeMember() {
  }

  // ///////////////////////////////////////////////////////////////////
  // Private Methods
  // ///////////////////////////////////////////////////////////////////
  private function createMemberObject($member) {
    $member = trim($member);
    $prefix = substr($member, 0, 1);
    if ($prefix === SvnAuthzFile::ALIAS_SIGN) {
      $alias = new SvnAuthzFileAlias();
      $alias->alias = substr($member, 1);
      return $alias;
    } else if ($prefix === SvnAuthzFile::GROUP_SIGN) {
      $group = new SvnAuthzFileGroup();
      $group->name = substr($member, 1);
      return $group;
    } else {
      $user = new SvnAuthzFileUser();
      $user->name = $member;
      return $user;
    }
  }

}
/**
 * header("Content-Type: text/plain; charset=utf-8");
 * include("IniFile.php");
 *
 * // Load file.
 * $authz = new SvnAuthzFile();
 * $authz->loadFromFile("D:/Development/Data/dav svn.authz.backup");
 * //print_r($authz->getAliases());
 * //print_r($authz->getGroups());
 * //print_r($authz->getPaths());
 * //print_r($authz->getMembersOfGroup("group_4"));
 *
 * $obj = new SvnAuthzFilePath();
 * $obj->repository = "repo_2_write";
 * $obj->path = "/subfolder";
 * print_r($authz->getPermissionsOfPath($obj));
 *
 * //print_r($authz);
 *
 * /*
 */
?>