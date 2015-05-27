<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN);

// Required variables.
$selusers  = get_request_var('selected_users');
$selgroups = get_request_var('selected_groups');
$selpaths  = get_request_var('selected_accesspaths');
$selperm   = get_request_var('permission'); // TODO: There is no check, whether this var is given!

if (count($selusers) == 1 && empty($selusers[0]))
  $selusers = NULL;
if (count($selgroups) == 1 && empty($selgroups[0]))
  $selgroups = NULL;
if (count($selpaths) == 1 && empty($selpaths[0]))
  $selpaths = NULL;

if( $selpaths == NULL || ( $selusers == NULL && $selgroups == NULL ) )
{
	$appEngine->addException(new ValidationException(tr("You have to select a user or group and an access-path to perform this action.")));
}
else
{
	try {
	  // The number of selected elements.
	  $selpathsLen = count($selpaths);
	  $selgroupsLen = ($selgroups != NULL) ? count($selgroups) : 0;
	  $selusersLen =  ($selusers  != NULL) ? count($selusers)  : 0;

	  // Create permission object.
	  $oP = new \svnadmin\core\entities\Permission;
	  $oP->perm = $selperm;

	  // Iterate all selected_accesspaths.
	  for( $i=0; $i<$selpathsLen; $i++ )
	  {
	    $oAP = new \svnadmin\core\entities\AccessPath;
	    $oAP->id = $selpaths[$i];
	    $oAP->path = $selpaths[$i];

	    // Is the user restricted to some paths? (project-manager)
	    if ($appEngine->isAuthenticationActive())
	    {
	      $currentUsername = $appEngine->getSessionUsername();
	      if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
	      {
	        if (!$appEngine->getAclManager()->isUserAdminOfPath($currentUsername, $oAP->path))
	        {
	          // Skip assignment.
	          $appEngine->addException(new Exception(tr("No administration permission for %0", array($oAP->path))));
	          continue;
	        }
	      }
	    }

		// Iterate selected_users.
		for( $iu=0; $iu<$selusersLen; $iu++ )
    	{
			$oU = new \svnadmin\core\entities\User;
			$oU->id = $selusers[$iu];
			$oU->name = $selusers[$iu];

			try {
				$b = $appEngine->getAccessPathEditProvider()->assignUserToAccessPath($oU, $oAP, $oP);
				if (!$b) {
					throw new Exception("ERROR");
				}
				$appEngine->addMessage(tr("Grant %0 permission to %1 on %2", array($oP->perm, $oU->name, $oAP->path)));
			}
			catch (Exception $e) {
				$appEngine->addException($e);
			}
    	}

	    // Iterate selected_groups.
	    for( $ig=0; $ig<$selgroupsLen; $ig++ )
	    {
	      $oG = new \svnadmin\core\entities\Group;
	      $oG->id = $selgroups[$ig];
	      $oG->name = $selgroups[$ig];

	      try {
	      	$b = $appEngine->getAccessPathEditProvider()->assignGroupToAccessPath( $oG, $oAP, $oP );
	      	if (!$b) {
	      		throw new Exception("ERROR");
	      	}
	      	$appEngine->addMessage(tr("Grant %0 permission to %1 on %2", array($oP->perm, $oG->name, $oAP->path)));
	      }
	      catch (Exception $e) {
	      	$appEngine->addException($e);
	      }
	    }
	  }

	  // Save changes!
	  $appEngine->getAccessPathEditProvider()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
