<?php
class GroupService extends ServiceBase {

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
      case "members":
        return $this->processMembers($request, $response);
      case "membergroups":
        return $this->processMemberGroups($request, $response);
      case "memberassign":
        return $this->processMemberAssignOrUnassign($request, $response, true);
      case "memberunassign":
        return $this->processMemberAssignOrUnassign($request, $response, false);
    }
    return parent::processRequest($request, $response);
  }

  public function processProviders(WebRequest $request, WebResponse $response) {
    $engine = SVNAdminEngine::getInstance();
    $providers = $engine->getKnownProviders(SVNAdminEngine::GROUP_PROVIDER);

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

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $itemList = $provider->getGroups($offset, $num);

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

    $list = SVNAdminEngine::getInstance()->startMultiProviderSearch(SVNAdminEngine::GROUP_PROVIDER, empty($providerId) ? array() : array($providerId), $query);
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
    if (empty($providerId) || empty($name)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $group = $provider->create($name);
    if ($group === null) {
      return $this->processErrorInternal($request, $response);
    }

    $json = new stdClass();
    $json->group = JsonSerializer::fromGroup($group);
    $response->done2json($json);
    return true;
  }

  public function processDelete(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $groupId = $request->getParameter("groupid");
    if (empty($providerId) || empty($groupId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getProvider(SVNAdminEngine::GROUP_PROVIDER, $providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    // TODO Remove user assignments.
    if (true) {
    }

    // TODO Remove permissions.
    if (true) {
    }

    if (!$provider->delete($groupId)) {
      return $this->processErrorInternal($request, $response);
    }
    return true;
  }

  public function processMembers(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $groupId = $request->getParameter("groupid");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);
    if (empty($providerId) || empty($groupId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getGroupMemberAssociater($providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $itemList = $provider->getMembersOfGroup($groupId, $offset, $num);
    $members = $itemList->getItems();

    $json = new stdClass();
    $json->hasmore = $itemList->hasMore();
    $json->members = array ();
    foreach ($members as &$member) {
      $json->members[] = JsonSerializer::fromGroupMember($member);
    }
    $response->done2json($json);
    return true;
  }

  public function processMemberGroups(WebRequest $request, WebResponse $response) {
    $providerId = $request->getParameter("providerid");
    $memberId = $request->getParameter("memberid");
    $offset = $request->getParameter("offset", 0);
    $num = $request->getParameter("num", 10);
    if (empty($providerId) || empty($memberId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $engine = SVNAdminEngine::getInstance();
    $provider = $engine->getGroupMemberAssociater($providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    $itemList = $provider->getGroupsOfMember($memberId, $offset, $num);

    $json = new stdClass();
    $json->list = JsonSerializer::fromItemList($itemList);
    $response->done2json($json);
    return true;
  }

  public function processMemberAssignOrUnassign(WebRequest $request, WebResponse $response, $assignOrUnassign) {
    $providerId = $request->getParameter("providerid");
    $groupId = $request->getParameter("groupid");
    $memberId = $request->getParameter("memberid");
    if (empty($providerId) || empty($groupId) || empty($memberId)) {
      return $this->processErrorMissingParameters($request, $response);
    }

    $provider = SVNAdminEngine::getInstance()->getGroupMemberAssociater($providerId);
    if (empty($provider)) {
      return $this->processErrorInvalidProvider($request, $response, $providerId);
    }

    if ($assignOrUnassign) {
      if (!$provider->assign($groupId, $memberId)) {
        return $this->processErrorInternal($request, $response);
      }
    } else {
      if (!$provider->unassign($groupId, $memberId)) {
        return $this->processErrorInternal($request, $response);
      }
    }
    return true;
  }

}
?>