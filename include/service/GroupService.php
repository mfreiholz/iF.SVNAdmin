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
      case "info":
        break;
      case "users":
        return $this->processUsers($request, $response);
      case "assignuser":
        return $this->processAssignUnassignUser($request, $response, true);
      case "unassignuser":
        return $this->processAssignUnassignUser($request, $response, false);
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

    $list = $provider->getGroups($offset, $num);
    $items = $list->getItems();

    $json = new stdClass();
    $json->editable = $provider->isEditable();
    $json->hasmore = $list->hasMore();
    $json->groups = array ();
    foreach ($items as &$group) {
      $jsonGroup = new stdClass();
      $jsonGroup->id = $group->getId();
      $jsonGroup->name = $group->getName();
      $jsonGroup->displayname = $group->getDisplayName();
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
    $jsonGroup->displayname = $group->getDisplayName();
    $json->group = $jsonGroup;

    $response->done2json($json);
    return true;
  }

  public function processDelete(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $id = $request->getParameter("groupid");

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

  public function processInfo(WebRequest $request, WebResponse $response) {
    return true;
  }

  public function processUsers(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $id = $request->getParameter("groupid");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);

    if (empty($providerId) || empty($id)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getAssociaterForGroups($providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $itemList = $provider->getUsersOfGroup($id, $offset, $num);
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

  public function processAssignUnassignUser(WebRequest $request, WebResponse $response, $assignOrUnassign) {
    $doAssign = $assignOrUnassign;
    $doUnassign = !$assignOrUnassign;
    $groupProviderId = $request->getParameter("providerid");
    $userId = $request->getParameter("userid");
    $groupId = $request->getParameter("groupid");

    if (empty($groupProviderId) || empty($userId) || empty($groupId)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getAssociaterForGroups($groupProviderId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    if ($doAssign) {
      if (!$provider->assign($userId, $groupId)) {
        $response->fail(500);
        $response->write2json(array (
            "message" => "Internal error."
        ));
        return true;
      }
    } else if ($doUnassign) {
      if (!$provider->unassign($userId, $groupId)) {
        $response->fail(500);
        $response->write2json(array (
            "message" => "Internal error."
        ));
        return true;
      }
    }

    $response->write2json(array (
        "status" => 0
    ));
    return true;
  }

}
?>