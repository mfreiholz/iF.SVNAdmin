<?php
// 检查常量'ACTION_HANDLING'是否存在
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
$reponame = get_request_var("reponame"); // 获取仓库名称
$repotype = get_request_var("repotype"); // 获取仓库类型fsfs或bdb
$repodesc = get_request_var("repodesc"); // 获取仓库描述信息

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);

//
// Validation
// 验证，如果仓库名称为空，那么就添加异常
if ($reponame == NULL) {
	$engine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else {
	$r = new \svnadmin\core\entities\Repository($reponame, $varParentIdentifier, $repodesc);

	// Create repository.
	try {

	    // 创建SVN仓库第1步，在svnreos根目录下面创建仓库文件夹
		$engine->getRepositoryEditProvider()->create($r, $repotype);

        // 创建SVN仓库第2步，会调用RepositoryEditProvider.class.php文件中RepositoryEditProvider类的save()方法
        // 实质没做什么，仅返回true
		$engine->getRepositoryEditProvider()->save();
		$engine->addMessage(tr("The repository %0 has been created successfully", array($reponame)));

		// Create the access path now.
		try {
			if (get_request_var("accesspathcreate") != NULL
				&& $engine->isProviderActive(PROVIDER_ACCESSPATH_EDIT)) {
				
				$ap = new \svnadmin\core\entities\AccessPath($reponame . ':/');

				if ($engine->getAccessPathEditProvider()->createAccessPath($ap)) {
                    $engine->addMessage('测试保存仓库2');
					$engine->getAccessPathEditProvider()->save();
                    $engine->addMessage('测试保存仓库3');
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