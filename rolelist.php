<?php
include("include/config.inc.php");

//
// Authentication
//

if (!$appEngine->isAclManagerActive())
{
	$appEngine->forwardInvalidModule(true);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_ROLE, ACL_ACTION_VIEW);
$appTR->loadModule("roles");

//
// View Data
//

$roles = $appEngine->getAclManager()->getRoles();

SetValue('RoleList', $roles);
ProcessTemplate('role/rolelist.html.php');
?>