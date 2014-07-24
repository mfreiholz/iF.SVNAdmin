<?php
class ServiceBase extends WebModule {

  public function getAppEngine() {
    return SVNAdminEngine::getInstance();
  }

  public function beforeProcessRequest(WebRequest $request, WebResponse $response) {
    session_start();

    // Check authentication.
    if (!isset($_SESSION["username"])) {
      $response->fail(401);
      session_destroy();
      return false;
    }

    return true;
  }

  public function afterProcessRequest(WebRequest $request, WebResponse $response) {
    return true;
  }

}