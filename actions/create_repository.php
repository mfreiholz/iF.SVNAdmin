<?php
if (!defined('ACTION_HANDLING'))
{
	die("HaHa!");
}

//
// Authentication
//

if (!$appEngine->isProviderActive(PROVIDER_REPOSITORY_EDIT))
{
	$appEngine->forwardError(ERROR_INVALID_MODULE);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_ADD);

//
// HTTP Request Vars
//

$reponame = get_request_var("reponame");
$repotype = get_request_var("repotype");

//
// Validation
//

if( $reponame == NULL )
{
	$appEngine->addException(new ValidationException($appTR->tr("You have to fill out all fields.")));
}
else
{
	$r = new \svnadmin\core\entities\Repository();
	$r->name = $reponame;

	// Create repository.
	try {
		$appEngine->getRepositoryEditProvider()->create($r, $repotype);
		$appEngine->getRepositoryEditProvider()->save();
		$appEngine->addMessage($appTR->tr("The repository %0 has been created successfully", array($reponame)));

		// Create the access path now.
		try {
			if (get_request_var("accesspathcreate") != NULL && $appEngine->isAccessPathEditActive())
			{
				$ap = new \svnadmin\core\entities\AccessPath;
				$ap->path = $reponame.":/";

				if ($appEngine->getAccessPathEditProvider()->createAccessPath($ap))
				{
					$appEngine->getAccessPathEditProvider()->save();
				}
			}
		}
		catch (Exception $e2) {
			$appEngine->addException($e2);
		}

		// Create a initial repository structure.
		try {
			$repoPredefinedStructure = get_request_var("repostructuretype");
			if ($repoPredefinedStructure != NULL)
			{
				switch ($repoPredefinedStructure)
				{
					case "single":
						$appEngine->getRepositoryEditProvider()->mkdir($r, "trunk");
						$appEngine->getRepositoryEditProvider()->mkdir($r, "branches");
						$appEngine->getRepositoryEditProvider()->mkdir($r, "tags");
						break;

					case "multi":
						$projectName = get_request_var("projectname");
						if ($projectName != NULL)
						{
							$appEngine->getRepositoryEditProvider()->mkdir($r, $projectName."/trunk");
							$appEngine->getRepositoryEditProvider()->mkdir($r, $projectName."/branches");
							$appEngine->getRepositoryEditProvider()->mkdir($r, $projectName."/tags");
						}
						else
						{
							throw new ValidationException($appTR->tr("Missing project name"));
						}
						break;
				}
			}
		}
		catch (Exception $e3) {
			$appEngine->addException($e3);
		}
	}
	catch (Exception $e) {
		$appEngine->addException($e);
	}
}
?>