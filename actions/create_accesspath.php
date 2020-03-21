<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule(!$appEngine->isAccessPathEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_ADD);

// Check required fields.
$path = get_request_var('path');
$accesspathdesc = get_request_var('accesspathdesc');  // Access Path Description info
$accesspathd_reason = get_request_var('accesspathreason');

// Check Access Path inputed.
if( $path == NULL or $accesspathd_reason == NULL)
{
  $appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else
{
  try {
    $doCreate = true;
    $p = new \svnadmin\core\entities\AccessPath();
    $p->path = $path;
    $p->description = $accesspathdesc;

    // Is the user restricted to some paths? (project-manager)
    if ($appEngine->isAuthenticationActive())
    {
      $currentUsername = $appEngine->getSessionUsername();


        // is access path manager
      if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
      {
        if (!$appEngine->getAclManager()->isUserAdminOfPath($currentUsername, $path))
        {
          $doCreate = false;
          $appEngine->addException(new Exception(tr("You don't have the permission to create this access path: %0", array($path))));
        }
      }

      // make sure the admin always can create access path
      $oUser = new \svnadmin\core\entities\User;
      $oUser->id = $currentUsername;
      $oUser->name = $currentUsername;
      $rolesOfUser = $appEngine->getAclManager()->getRolesOfUser($oUser);
      for ($i = 0; $i < count($rolesOfUser); ++$i) {
        if ($appEngine->getAclManager()->isAdminRole($rolesOfUser[$i])){
          $doCreate = true;
        }
      }
    }

    // Create now.
    if ($doCreate)
    {
      $b = $appEngine->getAccessPathEditProvider()->createAccessPath($p, $accesspathd_reason);
      if($b) {
        $appEngine->getAccessPathEditProvider()->save();
        $appEngine->addMessage(tr("Created AccessPath \"%0\" successfully.", array($path)));
      }
      else {
        throw new Exception(tr("An unknown error occured. Check your configuration. Maybe this AccessPath exist. please."));
      }
    }
  }
  catch (Exception $ex) {
    $appEngine->addException($ex);
  }
}
?>