<?php
if (!defined('ACTION_HANDLING')) {
  die("HaHa!");
}

$engine = \svnadmin\core\Engine::getInstance();

//
// Authentication
//

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)) {
  $engine->forwardError(ERROR_INVALID_MODULE);
}

$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_ADD);

//
// HTTP Request Vars
//

$varParentIdentifierEnc = get_request_var('pi');
$reponame = get_request_var("reponame"); // get the repository name
$repotype = get_request_var("repotype"); // get the repository type, can be 'fsfs' or 'bdb', default is 'fsfs'
$repodesc = get_request_var("repodesc"); // get the repository description or summary information.
$repo_reason = get_request_var("reporeason"); // get the reason creating this repository

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);

//
// Validation
// if the repository name is null will return exception
if ($reponame == NULL or $repo_reason == NULL) {
  $engine->addException(new ValidationException(tr("You have to fill out all fields.")));
} else {
  $r = new \svnadmin\core\entities\Repository($reponame, $varParentIdentifier, $repodesc);

  // Create repository.
  try {
    if_log_debug('Start to create repository');
    // Step 1: create the repository folder in the svn root folder
    $engine->getRepositoryEditProvider()->create($r, $repotype, $repo_reason);

    // Step 2: call the save() method in the RepositoryEditProvider.class.php
    // do nothing. just return true
    $engine->getRepositoryEditProvider()->save();
    $engine->addMessage(tr("The repository %0 has been created successfully", array($reponame)));

    // Step 3: Modify the config file after the repository folder created.
    if_log_debug('Created SVN repository in the SVN rootpath. Start to modify the ini config file');
    // Create the access path now.
    try {
      if (get_request_var("accesspathcreate") != NULL
        && $engine->isProviderActive(PROVIDER_ACCESSPATH_EDIT)) {
        // get the repository description information
        if_log_debug('The repository description information:' . $repodesc);

        // Step 4: Create the AccessPath object. and give the repository name and description information.
        // @see classes/entities/AccessPath.class.php
        $ap = new \svnadmin\core\entities\AccessPath($reponame . ':/', $repodesc);

        // Step 5: call the createAccessPath method. define @see classes/providers/AuthFileGroupAndPathsProvider.class.php
        // createAccessPath write the user config data to the $items array
        // $engine->getAccessPathEditProvider()->save(); will save the user data to the config file
        // Create the Access Path Object and save use data to config file
        if_log_debug('Create the Access Path Object and save use data to config file');
        if ($engine->getAccessPathEditProvider()->createAccessPath($ap)) {
          // save user data to the authz config file
          if_log_debug('Save data to the authz config file');
          $engine->getAccessPathEditProvider()->save();
          if_log_debug('Access Path created!');
        }
      }
    } catch (Exception $e2) {
      $engine->addException($e2);
    }

    // Create a initial repository structure.
    try {
      $repoPredefinedStructure = get_request_var("repostructuretype");
      if ($repoPredefinedStructure != NULL) {

        switch ($repoPredefinedStructure) {
          case "single":
            $engine->getRepositoryEditProvider()
              ->mkdir($r, array('trunk', 'branches', 'tags'));
            break;

          case "multi":
            $projectName = get_request_var("projectname");
            if ($projectName != NULL) {
              $engine->getRepositoryEditProvider()
                ->mkdir($r, array(
                  $projectName . '/trunk',
                  $projectName . '/branches',
                  $projectName . '/tags'
                ));
            } else {
              throw new ValidationException(tr("Missing project name"));
            }
            break;
        }
      }
    } catch (Exception $e3) {
      $engine->addException($e3);
    }
  } catch (Exception $e) {
    $engine->addException($e);
  }
}
?>