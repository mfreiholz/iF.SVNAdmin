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
      case "info":
        return $this->processInfo($request, $response);
      case "groups":
        return $this->processGroups($request, $response);
      case "assigngroup":
        return $this->processAssignUnassignGroup($request, $response, true);
      case "unassigngroup":
        return $this->processAssignUnassignGroup($request, $response, false);
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
    $id = $request->getParameter("userid");

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
    $id = $request->getParameter("userid");
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

  public function processInfo(WebRequest $request, WebResponse $response) {
    return false;
  }

  public function processGroups(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $id = $request->getParameter("userid");
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
    $provider = $engine->getAssociaterForUsers($providerId);

    if (empty($provider)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Internal error."
      ));
      return true;
    }

    $itemList = $provider->getGroupsOfUser($id, $offset, $num);
    $groups = $itemList->getItems();

    $json = new stdClass();
    $json->editable = $provider->isEditable();
    $json->hasmore = $itemList->hasMore();
    $json->groups = array ();
    foreach ($groups as &$group) {
      $jsonGroup = new stdClass();
      $jsonGroup->id = $group->getId();
      $jsonGroup->name = $group->getName();
      $jsonGroup->displayname = $group->getDisplayName();
      $json->groups[] = $jsonGroup;
    }
    $response->done2json($json);
    return true;
  }

  public function processAssignUnassignGroup(WebRequest $request, WebResponse $response, $assignOrUnassign) {
    $doAssign = $assignOrUnassign;
    $doUnassign = !$assignOrUnassign;
    $userProviderId = $request->getParameter("providerid");
    $userId = $request->getParameter("userid");
    $groupId = $request->getParameter("groupid");

    if (empty($userProviderId) || empty($userId) || empty($groupId)) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Invalid parameters."
      ));
      return true;
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getAssociaterForUsers($userProviderId);

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