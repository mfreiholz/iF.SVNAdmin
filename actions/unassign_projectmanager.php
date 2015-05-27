<?php
if(!defined('ACTION_HANDLING'))
{
  die("HaHa!");
}

$appEngine->forwardInvalidModule(!$appEngine->isAuthenticationActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_PROJECTMANAGER, ACL_ACTION_UNASSIGN);

$selusers = get_request_var("selected_users");
$selpaths = get_request_var("selected_accesspaths");

if ($selusers == null || $selpaths == null)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one access-path and one user.")));
}
else
{
	try {
	  $selusersCount = count($selusers);
	  $selpathsCount = count($selpaths);
	
	  $doneList = array();
	  $failedList = array();
	
	  for ($i=0; $i<$selusersCount; $i++)
	  {
	    for ($j=0; $j<$selpathsCount; $j++)
	    {
	      $b = $appEngine->getAclManager()->removeAccessPathAdmin($selpaths[$j], $selusers[$i]);
	      if ($b)
	      	$appEngine->addMessage(tr("Removed Project-Manager status of user %0 from %1", array($selusers[$i], $selpaths[$j])));
	      else
	      	$appEngine->addException(new Exception(tr("Can not remove Project-Manager status of user %0 from %1", array($selusers[$i], $selpaths[$j]))));
	    }
	  }
	  $appEngine->getAclManager()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>