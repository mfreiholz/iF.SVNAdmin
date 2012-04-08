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
$reponame = get_request_var("reponame");
$repotype = get_request_var("repotype");

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);

//
// Validation
//

if ($reponame == NULL) {
	$engine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else {
	$r = new \svnadmin\core\entities\Repository($reponame, $varParentIdentifier);

	// Create repository.
	try {
		$engine->getRepositoryEditProvider()->create($r, $repotype);
		$engine->getRepositoryEditProvider()->save();
		$engine->addMessage(tr("The repository %0 has been created successfully", array($reponame)));

		// Create the access path now.
		try {
			if (get_request_var("accesspathcreate") != NULL
				&& $engine->isProviderActive(PROVIDER_ACCESSPATH_EDIT)) {
				
				$ap = new \svnadmin\core\entities\AccessPath($reponame . ':/');

				if ($engine->getAccessPathEditProvider()->createAccessPath($ap)) {
					$engine->getAccessPathEditProvider()->save();
				}
			}
		}
		catch (Exception $e2) {
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
						}
						else {
							throw new ValidationException(tr("Missing project name"));
						}
						break;
				}
			}
		}
		catch (Exception $e3) {
			$engine->addException($e3);
		}
	}
	catch (Exception $e) {
		$engine->addException($e);
	}
}
?>