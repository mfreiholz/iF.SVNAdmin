<?php
if (!defined('ACTION_HANDLING'))
{
	die("HaHa!");
}

//
// Authentication
//

if (!$appEngine->isProviderActive(PROVIDER_USER_EDIT))
{
	$appEngine->forwardError(ERROR_INVALID_MODULE);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_ADD);

//
// HTTP Request Vars
//

$username = get_request_var('username');
$password = get_request_var('password');
$password2 = get_request_var('password2');

// Check required fields.
if ($username == NULL || $password == NULL || $password2 == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else if ($password != $password2)
{
	$appEngine->addException(new ValidationException(tr("The password's doesn't match each other.")));
}
else
{
  // Create user object.
  $u = new \svnadmin\core\entities\User;
  $u->id = $username;
  $u->name = $username;
  $u->password = $password;

  try {
	  // Create the user now.
	  $b = $appEngine->getUserEditProvider()->addUser($u);
	  if($b)
	  {
	    $appEngine->getUserEditProvider()->save();
	    $appEngine->addMessage(tr("The user %0 has been created successfully.", array($username)));
	  }
	  else
	  {
	  	$appEngine->addException(new Exception(tr("An unknown error occured. Check your configuration, please.")));
	  }
  }
  catch (Exception $ex) {
  	$appEngine->addException($ex);
  }
}
?>