<?php

class RepositoryService extends ServiceBase {

	public function processRequest(WebRequest $request, WebResponse $response) {
		$action = $request->getParameter("action");
		switch ($action) {
			// Repositories
			case "list":
				return $this->processRepositoryList($request, $response);
			case "create":
				return $this->processCreate($request, $response);
			case "delete":
				return $this->processDelete($request, $response);
			case "browse":
				return $this->processBrowse($request, $response);
			case "info":
				return $this->processInfo($request, $response);

			// Repository Paths.
			case "paths":
				return $this->processPaths($request, $response);
			case "permissions":
				return $this->processPathPermissions($request, $response);
			case "addpath":
				return $this->processPathCreate($request, $response);
			case "deletepath":
				return $this->processPathDelete($request, $response);
			case "assignpath":
				return $this->processPathAssign($request, $response);
			case "unassignpath":
				return $this->processPathUnassign($request, $response);
		}
		return false;
	}

	// GET
	public function processRepositoryList(WebRequest $request, WebResponse $response) {
		$providerId = $request->getParameter("providerid");
		$offset = $request->getParameter("offset", 0);
		$num = $request->getParameter("num", 10);
		if (empty($providerId))
			return $this->processErrorMissingParameters($request, $response);

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
		if (empty($provider))
			return $this->processErrorInvalidProvider($request, $response, $providerId);

		$response->done2json(
			JsonSerializer::fromItemList(
				$provider->getRepositories($offset, $num)
			)
		);
		return true;
	}

	// POST { providerid: 0, name: "" }
	public function processCreate(WebRequest $request, WebResponse $response) {
		$data = json_decode($request->getRequestBody());
		if (empty($data) || empty($data->providerid) || empty($data->name))
			return $this->processErrorMissingParameters($request, $response);

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $data->providerid);
		if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE))
			return $this->processErrorInvalidProvider($request, $response, $data->providerid);

		$repo = $provider->create($data->name, null);
		if (empty($repo))
			return $this->processErrorInternal($request, $response);

		$response->done2json(
			JsonSerializer::fromRepository($repo)
		);
		return true;
	}

	// DELETE { providerid: 0, id: 0 }
	public function processDelete(WebRequest $request, WebResponse $response) {
		$data = json_decode($request->getRequestBody());
		if (empty($data) || empty($data->providerid) || empty($data->id))
			return $this->processErrorMissingParameters($request, $response);

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $data->providerid);
		if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE))
			return $this->processErrorInvalidProvider($request, $response, $data->providerid);

		$repo = $provider->findRepository($data->id);
		if (empty($repo))
			return $this->processErrorCustom($request, $response, "Can not find repository (id=" . $repo . ")");

		// TODO Delete all permissions (optional).

		if (!$provider->delete($data->id))
			return $this->processErrorInternal($request, $response);

		$response->done2json(
			JsonSerializer::fromRepository($repo)
		);
		return true;
	}

	public function processBrowse(WebRequest $request, WebResponse $response) {
		return false;
	}

	public function processInfo(WebRequest $request, WebResponse $response) {
		$providerId = $request->getParameter("providerid");
		$repositoryId = $request->getParameter("repositoryid");
		if (empty($providerId) || empty($repositoryId)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
		if (empty($provider)) {
			return $this->processErrorInvalidProvider($request, $response, $providerId);
		}

		$json = new stdClass();
		$json->entry = $provider->getInfo($repositoryId);
		$response->done2json($json);
		return true;
	}

	public function processPaths(WebRequest $request, WebResponse $response) {
		$providerId = $request->getParameter("providerid");
		$repositoryId = $request->getParameter("repositoryid");
		if (empty($providerId) || empty($repositoryId)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
		if (empty($provider)) {
			return $this->processErrorInvalidProvider($request, $response, $providerId);
		}

		$repository = $provider->findRepository($repositoryId);
		$authz = $provider->getSvnAuthz($repositoryId);
		$paths = $authz->getPaths($repository->getName());

		$json = array();
		foreach ($paths as &$path) {
			$json[] = JsonSerializer::fromRepositoryPath($path);
		}
		$response->done2json($json);
		return true;
	}
	/*
			public function processPathCreate(WebRequest $request, WebResponse $response) {
				$providerId = $request->getParameter("providerid");
				$repositoryId = $request->getParameter("repositoryid");
				$path = $request->getParameter("path");
				if (empty($providerId) || empty($repositoryId)) {
					return $this->processErrorMissingParameters($request, $response);
				}

				$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
				if (empty($provider)) {
					return $this->processErrorInvalidProvider($request, $response, $providerId);
				}

				$repository = $provider->findRepository($repositoryId);
				$authz = $provider->getSvnAuthz($repositoryId);
				if (empty($repository) || empty($authz)) {
					return $this->processErrorInternal($request, $response);
				}

				$o = SvnAuthzFilePath::create($repository->getName(), $path);
				$authz->addPath($o);
				if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($authz)) {
					return $this->processErrorInternal($request, $response);
				}
				return true;
			}

			public function processPathDelete(WebRequest $request, WebResponse $response) {
				$providerId = $request->getParameter("providerid");
				$repositoryId = $request->getParameter("repositoryid");
				$path = $request->getParameter("path");
				if (empty($providerId) || empty($repositoryId)) {
					return $this->processErrorMissingParameters($request, $response);
				}

				$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
				if (empty($provider)) {
					return $this->processErrorInvalidProvider($request, $response, $providerId);
				}

				$repository = $provider->findRepository($repositoryId);
				$authz = $provider->getSvnAuthz($repositoryId);
				if (empty($repository) || empty($authz)) {
					return true;
				}

				$o = new SvnAuthzFilePath();
				$o->repository = $repository->getName();
				$o->path = $path;
				$authz->removePath($o);
				if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($authz)) {
					return $this->processErrorInternal($request, $response);
				}
				return true;
			}

			public function processPathPermissions(WebRequest $request, WebResponse $response) {
				$providerId = $request->getParameter("providerid");
				$repositoryId = $request->getParameter("repositoryid");
				$path = $request->getParameter("path");
				if (empty($providerId) || empty($repositoryId)) {
					return $this->processErrorMissingParameters($request, $response);
				}

				$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
				if (empty($provider)) {
					return $this->processErrorInvalidProvider($request, $response, $providerId);
				}

				$repository = $provider->findRepository($repositoryId);
				if (empty($repository)) {
					return $this->processErrorInternal($request, $response);
				}
				$authz = $provider->getSvnAuthz($repositoryId);
				$permissions = $authz->getPermissionsOfPath(SvnAuthzFilePath::create($repository->getName(), $path));
				usort($permissions, function ($a, $b) {
					if ($a->member->asMemberString() === $b->member->asMemberString()) {
						return 0;
					}
					return ($a->member->asMemberString() < $b->member->asMemberString()) ? -1 : 1;
				});

				$json = new stdClass();
				$json->permissions = array();
				foreach ($permissions as &$permission) {
					$jsonPerm = new stdClass();
					$jsonPerm->member = Elws::createMemberEntity($permission->member->asMemberString());
					$jsonPerm->permission = $permission->permission;
					$json->permissions[] = $jsonPerm;
				}
				$response->done2json($json);
				return true;
			}

			public function processPathAssign(WebRequest $request, WebResponse $response) {
				$providerId = $request->getParameter("providerid");
				$repositoryId = $request->getParameter("repositoryid");
				$path = $request->getParameter("path");
				$memberId = $request->getParameter("memberid");
				$permission = $request->getParameter("permission");
				if (empty($providerId) || empty($repositoryId) || empty($path) || empty($memberId)) {
					return $this->processErrorMissingParameters($request, $response);
				}

				$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
				if (empty($provider)) {
					return $this->processErrorInvalidProvider($request, $response, $providerId);
				}

				$repository = $provider->findRepository($repositoryId);
				$authz = $provider->getSvnAuthz($repositoryId);
				if (empty($repository) || empty($authz)) {
					return $this->processErrorCustom($request, $response, "Unknown repository");
				}

				$authzPath = SvnAuthzFilePath::create($repository->getName(), $path);
				$authzMember = $authz->createMemberObject($memberId);
				$authz->addPermission($authzPath, $authzMember, $permission);
				if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($authz)) {
					return $this->processErrorInternal($request, $response);
				}
				return true;
			}

			public function processPathUnassign(WebRequest $request, WebResponse $response) {
				$providerId = $request->getParameter("providerid");
				$repositoryId = $request->getParameter("repositoryid");
				$path = $request->getParameter("path");
				$memberId = $request->getParameter("memberid");
				if (empty($providerId) || empty($repositoryId) || empty($path) || empty($memberId)) {
					return $this->processErrorMissingParameters($request, $response);
				}

				$provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);
				if (empty($provider)) {
					return $this->processErrorInvalidProvider($request, $response, $providerId);
				}

				$repository = $provider->findRepository($repositoryId);
				$authz = $provider->getSvnAuthz($repositoryId);
				if (empty($repository) || empty($authz)) {
					return $this->processErrorCustom($request, $response, "Unknown repository");
				}

				$authzPath = SvnAuthzFilePath::create($repository->getName(), $path);
				$authzMember = $authz->createMemberObject($memberId);
				$authz->removePermission($authzPath, $authzMember);
				if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($authz)) {
					return $this->processErrorInternal($request, $response);
				}
				return true;
			}
		*/
}

//		if (false) {
//			try {
//				$repository = $provider->findRepository($id);
//				$authz = $provider->getSvnAuthz($id);
//				$paths = $authz->getPaths($repository->getName());
//				foreach ($paths as &$path) {
//					$authz->removePath($path);
//				}
//				if (count($paths) > 0) {
//					SVNAdminEngine::getInstance()->commitSvnAuthzFile($authz);
//				}
//			} catch (Exception $e) {
//				error_log("Error during purge of all repository permissions (repositoryid=" . $id . ")");
//			}
//		}