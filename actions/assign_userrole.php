<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule( !$appEngine->isAclManagerActive() );

// Get request vars.
$selroles = get_request_var("selected_assign_role_name");
$selusers = get_request_var("selected_users");

// Fallback to array of roles.
if ($selroles == NULL)
{
	$selroles = get_request_var("selected_roles");
}
else
{
	$selroles = array($selroles);
}

if (count($selroles) == 1 && empty($selroles[0]))
{
	$selroles = NULL;
}

// Validate selection.
if ($selroles == NULL || $selusers == NULL)
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