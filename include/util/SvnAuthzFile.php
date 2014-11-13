<?php
/**
 */
abstract class SvnAuthzFileMember {
  public abstract function asMemberString();

}

/**
 */
class SvnAuthzFileAlias extends SvnAuthzFileMember {
  public $alias = "";
  public $value = "";

  public function asMemberString() {
    return "&" . $this->alias;
  }

}

/**
 */
class SvnAuthzFileGroup extends SvnAuthzFileMember {
  public $name = "";

  public function asMemberString() {
    return "@" . $this->name;
  }

}

/**
 */
class SvnAuthzFileUser extends SvnAuthzFileMember {
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
    if (!empty($path)) {
      $s .= $this->path;
    } else {
      $s .= "/";
    }
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
  const ALREADY_EXISTS = 3;

  /*
   * @var IniFile
   */
  private $_ini = null;

  public function __construct() {
  }

  /**
   * @param string $path Path to the authz file.
   * @return int SvnAuthzFile::NO_ERROR, SvnAuthzFile::FILE_ERROR
   */
  public function loadFromFile($path) {
    $ini = new IniFile();
    if ($ini->loadFromFile($path) !== IniFile::NO_ERROR) {
      return SvnAuthzFile::FILE_ERROR;
    }
    $this->_ini = $ini;
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * The file given by $path will be overwritten, if the file arleady exists.
   * @param string $path Path to the authz file.
   * @return int SvnAuthzFile::NO_ERROR, SvnAuthzFile::FILE_ERROR, SvnAuthzFile::UNKNOWN_ERROR
   */
  public function writeToFile($path = null) {
    if (!$this->_ini) {
      return SvnAuthzFile::UNKNOWN_ERROR;
    }
    if ($this->_ini->writeToFile($path) !== IniFile::NO_ERROR) {
      return SvnAuthzFile::FILE_ERROR;
    }
    return SvnAuthzFile::NO_ERROR;
  }

  public function toString() {
    if ($this->_ini) {
      return $this->_ini->asString();
    }
    return "";
  }

  /**
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
   * @return array<SvnAuthzFilePath>
   */
  public function getPaths() {
    $paths = array ();
    $sections = $this->_ini->getSections();
    foreach ($sections as &$section) {
      if (strcasecmp($section->name, SvnAuthzFile::ALIAS_SECTION) === 0 || strcasecmp($section->name, SvnAuthzFile::GROUP_SECTION) === 0) {
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
   * @param SvnAuthzFileGroup $group
   * @return array<SvnAuthzFileAlias,SvnAuthzFileGroup,SvnAuthzFileUser>
   */
  public function getMembersOfGroup(SvnAuthzFileGroup $group) {
    $members = array ();
    $value = $this->_ini->getValue(SvnAuthzFile::GROUP_SECTION, $group->name, "");
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

  /**
   * @param SvnAuthzFileAlias $obj The alias to create.
   * @return SvnAuthzFile::NO_ERROR
   */
  public function addAlias(SvnAuthzFileAlias $obj) {
    $this->_ini->setValue(SvnAuthzFile::ALIAS_SECTION, $obj->alias, $obj->value);
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * @param SvnAuthzFileAlias $obj
   * @return SvnAuthzFile::NO_ERROR
   */
  public function removeAlias(SvnAuthzFileAlias $obj) {
    $this->_ini->removeValue(SvnAuthzFile::ALIAS_SECTION, $obj->alias);
    // TODO Remove from groups.
    // TODO Remove permissions.
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * Creates a new group.
   * @param SvnAuthzFileGroup $obj
   * @return int SvnAuthzFile::NO_ERROR, SvnAuthzFile::ALREADY_EXISTS
   */
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
    return !$exists ? SvnAuthzFile::NO_ERROR : SvnAuthzFile::ALREADY_EXISTS;
  }

  /**
   * @param SvnAuthzFileGroup $obj
   * @return SvnAuthzFile::NO_ERROR
   */
  public function removeGroup(SvnAuthzFileGroup $obj) {
    $this->_ini->removeValue(SvnAuthzFile::GROUP_SECTION, $obj->name);
    // TODO Remove from groups.
    // TODO Remove permissions.
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * @param SvnAuthzFileGroup $group
   * @param SvnAuthzFileMember $memberObj
   * @return int SvnAuthzFile::NO_ERROR, SvnAuthzFile::ALREADY_EXISTS
   */
  public function addMember(SvnAuthzFileGroup $group, SvnAuthzFileMember $memberObj) {
    $value = $this->_ini->getValue(SvnAuthzFile::GROUP_SECTION, $group->name, "");
    $membersParts = explode(",", $value);
    foreach ($membersParts as &$member) {
      $member = trim($member);
      if ($member === $memberObj->asMemberString()) {
        return SvnAuthzFile::ALREADY_EXISTS;
      }
    }
    $membersParts[] = $memberObj->asMemberString();
    $value = join(",", $membersParts);
    $this->_ini->setValue(SvnAuthzFile::GROUP_SECTION, $group->name, $value);
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * @param SvnAuthzFileGroup $group
   * @param SvnAuthzFileMember $memberObj
   * @return int SvnAuthzFile::NO_ERROR
   */
  public function removeMember(SvnAuthzFileGroup $group, SvnAuthzFileMember $memberObj) {
    $value = $this->_ini->getValue(SvnAuthzFile::GROUP_SECTION, $group->name, "");
    $membersParts = explode(",", $value);
    $members = array();
    foreach ($membersParts as &$member) {
      $member = trim($member);
      if ($member === $memberObj->asMemberString()) {
        continue;
      }
      $members[] = $member;
    }
    $value = join(",", $members);
    $this->_ini->setValue(SvnAuthzFile::GROUP_SECTION, $group->name, $value);
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * @param SvnAuthzFilePath $path
   * @param SvnAuthzFileMember $memberObj
   * @param string $perm
   * @return int SvnAuthzFile::NO_ERROR
   */
  public function addPermission(SvnAuthzFilePath $path, SvnAuthzFileMember $memberObj, $perm) {
    $section = $this->_ini->getSection($path->asString());
    if ($section) {
      foreach ($section->items as &$item) {
        if ($item->key === $memberObj->asMemberString()) {
          $item->value = $perm;
          return SvnAuthzFile::NO_ERROR;
        }
      }
    }
    $this->_ini->setValue($path->asString(), $memberObj->asMemberString(), $perm);
    return SvnAuthzFile::NO_ERROR;
  }

  /**
   * @param SvnAuthzFilePath $path
   * @param SvnAuthzFileMember $memberObj
   * @return int SvnAuthzFile::NO_ERROR
   */
  public function removePermission(SvnAuthzFilePath $path, SvnAuthzFileMember $memberObj) {
    $this->_ini->removeValue($path->asString(), $memberObj->asMemberString());
    return SvnAuthzFile::NO_ERROR;
  }

  private function createMemberObject($member) {
    $member = trim($member);
    $prefix = substr($member, 0, 1);
    if ($prefix === SvnAuthzFile::ALIAS_SIGN) {
      $alias = new SvnAuthzFileAlias();
      $alias->alias = substr($member, 1);
      $alias->value = $this->_ini->getValue(SvnAuthzFile::ALIAS_SECTION, $alias->alias);
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
/*
include("IniFile.php");
header("Content-Type: text/plain; charset=utf-8");

// Load file.
$authz = new SvnAuthzFile();
$authz->loadFromFile("D:/Development/Data/dav svn.authz.backup");

//print_r($authz->getAliases());
//print_r($authz->getGroups());
//print_r($authz->getPaths());
//print_r($authz->getMembersOfGroup("group_3"));

//$obj = new SvnAuthzFilePath();
//$obj->repository = "repo_2_write";
//$obj->path = "/subfolder";
//print_r($authz->getPermissionsOfPath($obj));

//$obj = new SvnAuthzFileAlias();
//$obj->alias = "jsterff";
//$obj->value = "CN=Jan Sterff,OU=Users,DC=insanefactory,DC=com";
//$authz->addAlias($obj);

//$obj = new SvnAuthzFileAlias();
//$obj->alias = "mfreiholz";
//$authz->removeAlias($obj);

//$obj = new SvnAuthzFileGroup();
//$obj->name = "testgroup01";
//$authz->addGroup($obj);

//$obj = new SvnAuthzFileGroup();
//$obj->name = "all_users";
//$authz->removeGroup($obj);

//$group = new SvnAuthzFileGroup();
//$group->name = "all_users";
//$user = new SvnAuthzFileUser();
//$user->name = "usermember1";
//$alias = new SvnAuthzFileAlias();
//$alias->alias = "mfreiholz";
//$authz->addMember($group, $user);
//$authz->addMember($group, $user);
//$authz->addMember($group, $alias);
//$authz->addMember($group, $alias);
//$authz->addMember($group, $group);
//$authz->addMember($group, $group);
//$authz->removeMember($group, $user);
//$authz->removeMember($group, $alias);
//$authz->removeMember($group, $group);

//$path = new SvnAuthzFilePath();
//$path->repository = "";
//$path->path = "";
//$user = new SvnAuthzFileUser();
//$user->name = "manuel";
//$group = new SvnAuthzFileGroup();
//$group->name = "all_users";
//$alias = new SvnAuthzFileAlias();
//$alias->alias = "mfreiholz";
//$authz->addPermission($path, $group, SvnAuthzFile::PERM_NONE);
//$authz->addPermission($path, $user, SvnAuthzFile::PERM_NONE);
//$authz->addPermission($path, $user, SvnAuthzFile::PERM_READ);
//$authz->addPermission($path, $alias, SvnAuthzFile::PERM_READWRITE);
//$authz->removePermission($path, $group);

//print_r($authz);
print($authz->toString());
*/
?>