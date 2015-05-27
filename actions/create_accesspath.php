<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule(!$appEngine->isAccessPathEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_ADD);

// Check required fields.
$path = get_request_var('path');
if( $path == NULL )
{
  $appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else
{
  try {
    $doCreate = true;
    $p = new \svnadmin\core\entities\AccessPath();
    $p->path = $path;

    // Is the user restricted to some paths? (project-manager)
    if ($appEngine->isAuthenticationActive())
    {
      $currentUsername = $appEngine->getSessionUsername();
      if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
      {
        if (!$appEngine->getAclManager()->isUserAdminOfPath($currentUsername, $path))
        {
          $doCreate = false;
          $appEngine->addException(new Exception(tr("You don't have the permission to create this access path: %0", array($path))));
        }
      }
    }

    // Create now.
    if ($doCreate)
    {
      $b = $appEngine->getAccessPathEditProvider()->createAccessPath($p);
      if($b) {
        $appEngine->getAccessPathEditProvider()->save();
        $appEngine->addMessage(tr("Created AccessPath \"%0\" successfully.", array($path)));
      }
      else {
        throw new Exception(tr("An unknown error occured. Check your configuration, please."));
      }
    }
  }
  catch (Exception $ex) {
    $appEngine->addException($ex);
  }
}
?>