<?php
class LoginService extends WebModule {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action", "login");
    if ($action === "check") {
      return $this->processCheck($request, $response);
    } else if ($action === "login") {
      return $this->processLogin($request, $response);
    } else if ($action === "logout") {
      return $this->processLogout($request, $response);
    }
    return false;
  }

  public function processCheck(WebRequest $request, WebResponse $response) {
    session_start();
    if (!isset($_SESSION["username"])) {
      $response->fail(401);
      session_destroy();
      return false;
    }
    return true;
  }

  public function processLogin(WebRequest $request, WebResponse $response) {
    $appEngine = SVNAdminEngine::getInstance();
    $username = $request->getParameter("username");
    $password = $request->getParameter("password");

    $authOk = false;
    $authId = null;
    $authenticators = $appEngine->getAuthenticators();
    foreach ($authenticators as $id => &$authenticator) {
      if ($authenticator->authenticate($username, $password)) {
        $authOk = true;
        $authId = $id;
        break;
      }
    }

    if (!$authOk) {
      $response->fail(401);
      return true;
    }

    // Create session
    session_start();
    $_SESSION["username"] = $username;

    // Response
    $ret = new stdClass();
    $ret->authenticatorid = $authId;
    $ret->sessionid = session_id();
    $ret->username = $username;
    $response->done2json($ret);
    return true;
  }

  public function processLogout(WebRequest $request, WebResponse $response) {
    session_start();
    session_destroy();
  }

}
?>