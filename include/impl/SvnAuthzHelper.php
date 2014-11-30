<?php
/**
 * Class SvnAuthzHelper
 * Helps all implementations which are based on the Subverions authz file (SvnAuthzFile).
 */
class SvnAuthzHelper {

  public static function createGroupObject(SvnAuthzFileGroup $group) {
    $o = new Group();
    $o->initialize($group->asMemberString(), $group->name);
    return $o;
  }

}