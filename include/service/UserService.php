<?php
class UserService extends ServiceBase {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action");
    switch ($action) {
      case "providers":
        return $this->processProviders($request, $response);
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

  public function processProviders(WebRequest $request, WebResponse $response) {
    $engine = SVNAdminEngine::getInstance();
    $providers = $engine->getKnownProviders(SVNAdminEngine::USER_PROVIDER);
    $json = array ();
    foreach ($providers as &$prov) {
      $json[] = JsonSerializer::fromProvider($prov);
    }
    $response->done2json($json);
    return true;
  }

  public function processList(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);
    if (empty($providerId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $itemList = $provider->getUsers($offset, $num);

    $json = new stdClass();
    $json->list = JsonSerializer::fromItemList($itemList);
    $response->done2json($json);
    return true;
  }

  public function processSearch(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $query = $request->getParameter("query");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);
    if (empty($query)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $list = SVNAdminEngine::getInstance()->startMultiProviderSearch(SVNAdminEngine::USER_PROVIDER, empty($providerId) ? array() : array($providerId), $query);
    if (empty($list)) {
      return $this->processErrorInternal($request, $response);
    }

    $json = new stdClass();
    $json->list = JsonSerializer::fromItemList($list);
    $response->done2json($json);
    return true;
  }

  public function processCreate(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $name = $request->getParameter("name");
    $password = $request->getParameter("password");
    if (empty($providerId) || empty($name) || empty($password)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);
    if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $user = null;
    if (($user = $provider->create($name, $password)) === null) {
      return $this->processErrorInternal($request, $response);
    }

    $json = new stdClass();
    $json->user = JsonSerializer::fromUser($user);
    $response->done2json($json);
    return true;
  }

  public function processDelete(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $userId = $request->getParameter("userid");
    if (empty($providerId) || empty($userId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);
    if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
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

    if (!$provider->delete($userId)) {
      return $this->processErrorInternal($request, $response);
    }
    return true;
  }

  public function processChangePassword(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $userId = $request->getParameter("userid");
    $password = $request->getParameter("password");
    if (empty($providerId) || empty($userId) || empty($password)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $provider = SVNAdminEngine::getInstance()->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);
    if (empty($provider) || !$provider->hasFlag(Provider::FLAG_EDITABLE)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    if (!$provider->changePassword($userId, $password)) {
      return $this->processErrorInternal($request, $response);
    }
    return true;
  }

}
?>