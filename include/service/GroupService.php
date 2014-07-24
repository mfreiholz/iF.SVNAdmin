<?php
class GroupService extends ServiceBase {

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
    }
    return parent::processRequest($request, $response);
  }

  public function processProviders(WebRequest $request, WebResponse $response) {
    $engine = SVNAdminEngine::getInstance();
    $providers = $engine->getKnownProviders(SVNAdminEngine::GROUP_PROVIDER);

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
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $groups = $provider->getGroups($offset, $num);

    $json = new stdClass();
    $json->editable = $provider->isEditable();
    $json->hasmore = false;
    $json->groups = array ();
    foreach ($groups as &$group) {
      $jsonGroup = new stdClass();
      $jsonGroup->id = $group->getId();
      $jsonGroup->name = $group->getName();
      $jsonGroup->displayName = $group->getDisplayName();
      $json->groups[] = $jsonGroup;
    }
    $response->done2json($json);
    return true;
  }

  public function processCreate(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $name = $request->getParameter("name");

    if (empty($providerId) || empty($name)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $group = $provider->create($name);
    if ($group === null) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $json = new stdClass();
    $jsonGroup = new stdClass();
    $jsonGroup->id = $group->getId();
    $jsonGroup->name = $group->getName();
    $jsonGroup->displayName = $group->getDisplayName();
    $json->group = $jsonGroup;

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
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);

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

}
?>