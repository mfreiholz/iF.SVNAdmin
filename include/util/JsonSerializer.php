<?php
class JsonSerializer {

  public static function fromProvider($provider) {
    $j = new stdClass();
    $j->id = $provider->id;
    $j->editable = false;
    return $j;
  }

  public static function fromUser(User $user) {
    $j = new stdClass();
    $j->id = $user->getId();
    $j->name = $user->getName();
    $j->displayname = $user->getDisplayName();
    if (property_exists($user, "providerid")) {
      $j->providerid = $user->providerid;
    }
    return $j;
  }

  public static function fromGroup(Group $group) {
    $j = new stdClass();
    $j->id = $group->getId();
    $j->name = $group->getName();
    $j->displayname = $group->getDisplayName();
    if (property_exists($group, "providerid")) {
      $j->providerid = $group->providerid;
    }
    return $j;
  }

  public static function fromGroupMember(GroupMember $member) {
    $j = new stdClass();
    $j->id = $member->getId();
    $j->name = $member->getName();
    $j->displayname = $member->getDisplayName();
    $j->type = $member->getType();
    return $j;
  }

  public static function fromItemList(ItemList $itemList) {
    $j = new stdClass();
    $j->hasmore = $itemList->hasMore();
    $j->items = array();
    foreach ($itemList->getItems() as &$item) {
      if ($item instanceof User) {
        $j->items[] = JsonSerializer::fromUser($item);
      } else if ($item instanceof Group) {
        $j->items[] = JsonSerializer::fromGroup($item);
      } else if ($item instanceof GroupMember) {
        $j->items[] = JsonSerializer::fromGroupMember($item);
      }
    }
    return $j;
  }

}