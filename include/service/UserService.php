<?php
class UserService extends ServiceBase {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action");
    switch ($action) {
      case "providers":
        return $this->processProviders($request, $response);
      case "list":
        return $this->processList($request, $response);
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

    $respProviders = array ();
    foreach ($providers as &$prov) {
      $respProv = new stdClass();
      $respProv->id = $prov->id;
      $respProv->editable = null;
      $respProviders[] = $respProv;
    }

    $response->done2json($respProviders);
    return true;
  }

  public function processList(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);

    if (empty($providerId)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Missing parameter 'providerid'."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $itemList = $provider->getUsers($offset, $num);
    $users = $itemList->getItems();

    $json = new stdClass();
    $json->editable = $provider->isEditable();
    $json->hasmore = $itemList->hasMore();
    $json->users = array ();
    foreach ($users as &$user) {
      $jsonUser = new stdClass();
      $jsonUser->id = $user->getId();
      $jsonUser->name = $user->getName();
      $jsonUser->displayname = $user->getDisplayName();
      $json->users[] = $jsonUser;
    }
    $response->done2json($json);
    return true;
  }

  public function processCreate(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $name = $request->getParameter("name");
    $password = $request->getParameter("password");

    if (empty($providerId) || empty($name) || empty($password)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $user = null;
    if (($user = $provider->create($name, $password)) === null) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $json = new stdClass();
    $jsonUser = new stdClass();
    $jsonUser->id = $user->getId();
    $jsonUser->name = $user->getName();
    $jsonUser->displayname = $user->getDisplayName();
    $json->user = $jsonUser;

    $response->done2json($json);
    return true;
  }

  public function processDelete(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $id = $request->getParameter("id");

    if (empty($providerId) || empty($id)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    if (!$provider->delete($id)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $response->write2json(array (
        "status" => 0
    ));
    return true;
  }

  public function processChangePassword(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $id = $request->getParameter("id");
    $password = $request->getParameter("password");

    if (empty($providerId) || empty($id) || empty($password)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::USER_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    if (!$provider->changePassword($id, $password)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $response->write2json(array (
        "status" => 0
    ));
    return true;
  }

}
?>