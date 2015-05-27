<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule(!$appEngine->isAuthenticationActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_PROJECTMANAGER, ACL_ACTION_ASSIGN);

$selusers = get_request_var("selected_users");
$selpaths = get_request_var("selected_accesspaths");

if ($selusers == null || $selpaths == null)
{
  $appEngine->addException(new ValidationException(tr("You have to select at least one access-path and one user.")));
}
else
{
  $selusersCount = count($selusers);
  $selpathsCount = count($selpaths);
  
  try
  {
    for ($i=0; $i<$selusersCount; $i++)
    {
      for ($j=0; $j<$selpathsCount; $j++)
      {
        // It is not possible to set an admin for root.
        if ($selpaths[$j] == "/")
        {
          continue;
        }

        if ($appEngine->getAclManager()->assignAccessPathAdmin($selpaths[$j], $selusers[$i]))
          $appEngine->addMessage(tr("Assigned user %0 to access-path %1 successfully.", array($selusers[$i], $selpaths[$i])));
        else
          $appEngine->addException(new Exception(tr("Could not assign user %0 to access-path %1", array($selusers[$i], $selpaths[$i]))));
      }
    }
    $appEngine->getAclManager()->save();
  }
  catch (Exception $ex)
  {
    $appEngine->addException($ex);
  }
}
?>
