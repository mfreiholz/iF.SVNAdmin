<?php
/**
 * iF.SVNAdmin
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
include("../../../ifphplib/IF_AbstractLdapConnector.class.php");
include("LdapUserViewProvider.class.php");

$obj = new \svnadmin\providers\ldap\LdapUserViewProvider;
$obj->users_base_dn = 'OU=CustomUsers,DC=augsburg,DC=insanefactory';
$obj->users_attributes = array('sAMAccountName','memberOf');;
$obj->groups_base_dn = 'OU=CustomGroups,DC=augsburg,DC=insanefactory';

if( $obj->connect("ldap://192.168.178.28") )
{
  print("Verbindung erfolgreich<br>");
  if( $obj->bind("CN=Manuel Freiholz,OU=CustomUsers,DC=augsburg,DC=insanefactory","test") )
  {
    print("Authentifizierung erfolgreich<br>");
    print("<hr>\n");
    
    ///////////////////////////////////////////////////////////////////////////
    
    $users = $obj->getUsers(2);
    for($i=0; $i<count($users); $i++)
    {
      print("<b>User:</b> ".$users[$i]->dn."<br>");
      print("<b>Login:</b> ".$users[$i]->samaccountname."<br>");
      /*
      print("<b>Groups:</b>");
      for($g=0; $g<count($users[$i]->memberof); $g++)
      {
        print("Group: ".$users[$i]->memberof[$g]."<br>");
      }
      */
      print("<hr>\n");
    }
    
    ///////////////////////////////////////////////////////////////////////////

    print("Get groups<br>");
    $groups = $obj->getGroups(2);
    for($i=0; $i<count($groups); $i++)
    {
      print("<b>Group-DN:</b> ".$groups[$i]->dn."<br>");
      print("<b>Group-CN:</b> ".$groups[$i]->cn."<br>");
      print("<hr>\n");
    }

    ///////////////////////////////////////////////////////////////////////////
    
    $theDN = "CN=Manuel Freiholz,OU=CustomUsers,DC=augsburg,DC=insanefactory";
    print("Get defined user: $theDN<br>");
    $u = $obj->getUserAttributes($theDN,array("sAMAccountName"));
    print("<b>User-&gt;sAMAccountName:</b> ".$u->samaccountname."<br>");
    print("<hr>\n");
    
    ///////////////////////////////////////////////////////////////////////////
    
    $theGroupDN = "CN=svnadmin,OU=CustomGroups,DC=augsburg,DC=insanefactory";
    print("Get users of group: $theGroupDN<br>");
    $groupUsers = $obj->getUsersOfGroup($theGroupDN, 0);
    for($i=0; $i<count($groupUsers); $i++)
    {
      print("<b>User-DN:</b> ".$groupUsers[$i]->dn."<br>");
    }
    print("<hr>\n");
    
    ///////////////////////////////////////////////////////////////////////////
    
    $theUserDN = "CN=Manuel Freiholz,OU=CustomUsers,DC=augsburg,DC=insanefactory";
    print("Get groups of user: $theUserDN<br>");
    $userGroups = $obj->getGroupsOfUser($theUserDN, 0);
    for($i=0; $i<count($userGroups); $i++)
    {
      print("<b>Group-DN:</b> ".$userGroups[$i]->dn."<br>");
    }
    print("<hr>\n");
    
    ///////////////////////////////////////////////////////////////////////////
  }
  else
  {
    print("Authentifizierung fehlgeschlagen<br>");
  }
}
else
{
  print("Verbindung fehlgeschlagen<br>");
}
?>