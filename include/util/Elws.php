<?php
/**
 * The Elws class is used to provide all kind of utility functions.
 * (Elws => German: "Eierlegende Wollmilchsau")
 */
class Elws {

  public static function normalizeAbsolutePath($path) {
    if (empty($path)) {
      return $path;
    }
    //$path = realpath($path);
    $path = str_replace("\\", "/", $path);
    return $path;
  }

  public static function listDirectory($dirPath) {
    if (empty($dirPath)) {
      return array();
    }
    $dh = opendir($dirPath);
    if (!is_resource($dh)) {
      return array();
    }
    $entries = array();
    while (($entry = readdir($dh)) !== false) {
      if ($entry === "." || $entry === "..") {
        continue;
      }
      $entries[] = $entry;
    }
    closedir($dh);
    return $entries;
  }

  public static function createMemberEntity($memberString) {
    $obj = new stdClass();
    $obj->id = $memberString;
    $prefix = substr($memberString, 0, 1);
    if ($prefix === "@") {
      $obj->type = "group";
      $obj->displayname = substr($memberString, 1);
    } else if ($prefix === "&") {
      $obj->type = "alias";
      $obj->displayname = substr($memberString, 1);
    } else {
      $obj->type = "user";
      $obj->displayname = $memberString;
    }
    return $obj;
  }

}