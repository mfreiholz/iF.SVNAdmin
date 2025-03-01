<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule( !$appEngine->isAclManagerActive() );

// Get request vars.
// If a single role is given, we convert it into an array, otherwise lets fallback to array of roles parameter.
$selroles = get_request_var("selected_assign_role_name");
$selroles = $selroles != NULL ? array($selroles) : get_request_var("selected_roles", array());
$selusers = get_request_var("selected_users", array());

// Validate selection.
if (count($selroles) <= 0 || count($selusers) <= 0)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one user and one role.")));
}
else
{
	try {
	  // Iterate all selected users and roles.
	  for ($i=0; $i<count($selroles); $i++)
	  {
	    $oR = new \svnadmin\core\entities\Role;
	    $oR->name = $selroles[$i];

	    for ($j=0; $j<count($selusers); $j++)
	    {
	      // Skip * user.
	      if ($selusers[$j] == "*")
	        continue;

	      $oU = new \svnadmin\core\entities\User;
	      $oU->name = $selusers[$j];

	      // Skip assignment, if the user doesn't have permission to assign the "admin" role.
	      if ($appEngine->getAclManager()->isAdminRole($oR) && !$appEngine->hasPermission(ACL_MOD_ROLE, ACL_ACTION_ASSIGN_ADMIN_ROLE))
	      {
	        $appEngine->addException(new Exception(tr("Can not assign user %0 to role %1", array($oU->name, $oR->name))));
	        continue;
	      }

	      if ($appEngine->getAclManager()->assignUserToRole($oU, $oR))
	      {
	      	$appEngine->addMessage(tr("The user %0 has been assigned to role %1", array($oU->name, $oR->name)));
	      }
	      else
	      {
	      	$appEngine->addException(new Exception(tr("Can not assign user %0 to role %1", array($oU->name, $oR->name))));
	      }
	    } //for
	  } //for

	  $appEngine->getAclManager()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>