<?php
include("include/config.inc.php");
$appEngine->forwardInvalidModule(!$appEngine->isUserEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_CHANGEPASS);
$appTR->loadModule("userchangepass");

// Action handling.
// Form request to create the user
$create = check_request_var('changepass');
if ($create)
{
	$appEngine->handleAction('change_password');
}

$encUsername = get_request_var("username");
$username = rawurldecode($encUsername);

$sessUser = $appEngine->getSessionUsername();
if ($sessUser != NULL)
{
	if ($username == NULL)
	{
		$username = $sessUser;
	}

	if ($sessUser != $username)
	{
		$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_CHANGEPASS_OTHER);
	}
}

SetValue("Username", $username);
ProcessTemplate("user/userchangepassword.html.php");
?>