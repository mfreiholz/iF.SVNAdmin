<?php
class RepositoryService extends WebModule {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action");
    switch ($action) {
      case "providers":
        return $this->processProviders($request, $response);
      case "list":
        return $this->processRepositoryList($request, $response);
      case "create":
        return $this->processCreate($request, $response);
      case "delete":
        return $this->processDelete($request, $response);
    }
    return false;
  }

  public function processProviders(WebRequest $request, WebResponse $response) {
    $engine = SVNAdminEngine::getInstance();
    $providers = $engine->getKnownProviders(SVNAdminEngine::REPOSITORY_PROVIDER);

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

  public function processRepositoryList(WebRequest $request, WebResponse $response) {
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
    $provider = $engine->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $repos = $provider->getRepositories($offset, $num);

    $json = new stdClass();
    $json->editable = $provider->isEditable();
    $json->hasmore = false;
    $json->repositories = array ();
    foreach ($repos as &$repo) {
      $o = new stdClass();
      $o->id = $repo->getId();
      $o->name = $repo->getName();
      $o->displayName = $repo->getDisplayName();
      $json->repositories[] = $o;
    }
    $response->done2json($json);
    return true;
  }

  public function processCreate(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $name = $request->getParameter("name");
    $options = $request->getParameter("options");

    if (empty($providerId) || empty($name)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);

    if (empty($provider) || !$provider->isEditable()) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    $repo = $provider->create($name, $options);
    if (empty($repo)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $json = new stdClass();
    $o = new stdClass();
    $o->id = $repo->getId();
    $o->name = $repo->getName();
    $o->displayName = $repo->getDisplayName();
    $json->repository = $o;
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
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::REPOSITORY_PROVIDER, $providerId);

    if (empty($provider) || !$provider->isEditable()) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid provider '" . $providerId . "'."
      ));
      return true;
    }

    if (!$provider->delete($id)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Can not delete repository (id=" . $id . ")"
      ));
      return true;
    }

    return true;
  }

}
?>