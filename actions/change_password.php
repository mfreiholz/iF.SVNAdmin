<?php if (!defined('ACTION_HANDLING')) { die("HaHa!"); }
$appEngine->forwardInvalidModule(!$appEngine->isUserEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_CHANGEPASS);

// Parameters.
$password = get_request_var("password");
$password2 = get_request_var("password2");
$username = get_request_var("username");

// Validation.
$sessionUser = $appEngine->getSessionUsername();
if ($sessionUser != NULL) {
  if ($sessionUser != $username) {
    $appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_CHANGEPASS_OTHER);
  }
}

if ($username == NULL) {
  $appEngine->addException(new ValidationException(tr("No username given.")));
}
else if ($password == NULL || $password2 == NULL) {
  $appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else if($password != $password2) {
  $appEngine->addException(new ValidationException(tr("The password's doesn't match each other.")));
}
else {
  try {
    // Ok, change password now.
    $b = $appEngine->getUserEditProvider()->changePassword($username, $password);
    if ($b) {
      $appEngine->getUserEditProvider()->save();
      $appEngine->addMessage(tr("The password has been changed."));
    }
    else {
      throw new Exception(tr("An unknown error occured. Check your configuration, please."));
    }
  }
  catch (Exception $ex) {
    $appEngine->addException($ex);
  }
}
?>