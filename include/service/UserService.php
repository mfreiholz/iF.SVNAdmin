<?php

class UserService extends ServiceBase {

	public function processRequest(WebRequest $request, WebResponse $response) {
		$action = $request->getParameter("action");
		switch ($action) {
			case "list":
				return $this->processList($request, $response);
			case "search":
				return $this->processSearch($request, $response);
			case "create":
				return $this->processCreate($request, $response);
			case "delete":
				return $this->processDelete($request, $response);
			case "changepassword":
				return $this->processChangePassword($request, $response);
		}
		return parent::processRequest($request, $response);
	}

	// GET
	public function processList(WebRequest $request, WebResponse $response) {
		$providerId = $request->getParameter("providerid");
		$offset = $request->getParameter("offset", 0);
		$num = $request->getParameter("num", 10);
		if (empty($providerId)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);
		if (empty($provider)) {
			return $this->processErrorInvalidProvider($request, $response, $providerId);
		}

		$response->done2json(
			JsonSerializer::fromItemList(
				$provider->getUsers($offset, $num)
			)
		);
		return true;
	}

	// GET
	public function processSearch(WebRequest $request, WebResponse $response) {
		$providerId = $request->getParameter("providerid");
		$query = $request->getParameter("query");
		$offset = $request->getParameter("offset", 0);
		$num = $request->getParameter("num", 10);
		if (empty($query)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$list = $this->getAppEngine()->startMultiProviderSearch(SVNAdminEngine::USER_PROVIDER, empty($providerId) ? array() : array($providerId), $query);
		if (empty($list)) {
			return $this->processErrorInternal($request, $response);
		}

		$response->done2json(JsonSerializer::fromItemList($list));
		return true;
	}

	// POST { providerid: 0, name: "", password: "" }
	public function processCreate(WebRequest $request, WebResponse $response) {
		$data = json_decode($request->getRequestBody());
		if (empty($data) || empty($data->providerid) || empty($data->name) || empty($data->password)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::USER_PROVIDER, $data->providerid);
		if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
			return $this->processErrorInvalidProvider($request, $response, $data->providerid);
		}

		$user = null;
		if (($user = $provider->create($data->name, $data->password)) === null) {
			return $this->processErrorInternal($request, $response);
		}

		$response->done2json(JsonSerializer::fromUser($user));
		return true;
	}

	// DELETE { providerid: 0, id: 0 }
	public function processDelete(WebRequest $request, WebResponse $response) {
		$data = json_decode($request->getRequestBody());
		if (empty($data) || empty($data->providerid) || empty($data->id)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::USER_PROVIDER, $data->providerid);
		if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
			return $this->processErrorInvalidProvider($request, $response, $data->providerid);
		}

		// TODO Remove group associations.
		if (true) {
		}

		// TODO Remove direct permissions.
		if (true) {
		}

		// TODO Remove role associations.
		if (true) {
		}

		if (!$provider->delete($data->id)) {
			return $this->processErrorInternal($request, $response);
		}
		return true;
	}

	// PUT { providerid: 0, id: 0, password: "" }
	public function processChangePassword(WebRequest $request, WebResponse $response) {
		$data = json_decode($request->getRequestBody());
		if (empty($data) || empty($data->providerid) || empty($data->id) || empty($data->password)) {
			return $this->processErrorMissingParameters($request, $response);
		}

		$provider = $this->getAppEngine()->getProvider(SVNAdminEngine::USER_PROVIDER, $data->providerid);
		if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
			return $this->processErrorInvalidProvider($request, $response, $data->providerid);
		}

		if (!$provider->changePassword($data->id, $data->password)) {
			return $this->processErrorInternal($request, $response);
		}
		return true;
	}

}