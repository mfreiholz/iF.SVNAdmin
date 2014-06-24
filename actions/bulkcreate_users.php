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

$usernames = get_request_var('usernames');

// Check required fields.
if ($usernames == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to provide a list of user names")));
}
else
{

        $usernames = array_values(array_map('strtolower', array_filter(preg_split( "/(\r?\n|;|,|\t)/", $usernames, -1, PREG_SPLIT_NO_EMPTY))));

        foreach($usernames as $username) {
              $password = randomPassword(8);

              // Create user object.
              $u = new \svnadmin\core\entities\User;
              $u->id = $username;
              $u->name = $username;
              $u->password = $password;

              // Create the user now.
              try {
                      $b = $appEngine->getUserEditProvider()->addUser($u);
                      $b = true;
                      if($b)
                      {
                              $appEngine->getUserEditProvider()->save();
                              $appEngine->addMessage(tr("The user %0 has been created with $password", array($username)));
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
}

function randomPassword($length) {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

?>
