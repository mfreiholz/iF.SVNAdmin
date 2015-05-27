<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule( !$appEngine->isGroupEditActive() );

// Parameters.
$name = get_request_var('name');

// Validation
if ($name == NULL)
{
  $appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else
{
  // Create user object.
  $g = new \svnadmin\core\entities\Group;
  $g->id = $name;
  $g->name = $name;
  
  // Create the user now.
  try
  {
    if ($appEngine->getGroupEditProvider()->addGroup($g))
    {
      $appEngine->addMessage(tr("The group %0 has been created successfully.", array($g->name)));
      $appEngine->getGroupEditProvider()->save();
    }
    else
    {
      $appEngine->addException(new Exception(tr("An unknown error occured. Check your configuration, please.")));
    }
  }
  catch (Exception $ex)
  {
    $appEngine->addException($ex);
  }
}
?>
