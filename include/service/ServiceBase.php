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

  protected function processErrorInternal(WebRequest $request, WebResponse $response) {
    $response->fail(500);
    $response->write2json(array("message" => "Internal error"));
    return true;
  }

  protected function processErrorCustom(WebRequest $request, WebResponse $response, $message) {
    $response->fail(500);
    $response->write2json(array("message" => $message));
    return true;
  }

  protected function processErrorMissingParameters(WebRequest $request, WebResponse $response) {
    $response->fail(500);
    $response->write2json(array("message" => "Missing parameters"));
    return true;
  }

  protected function processErrorInvalidProvider(WebRequest $request, WebResponse $response, $providerId) {
    $response->fail(500);
    $response->write2json(array("message" => "Invalid provider (providerid=" . $providerId . ")"));
    return true;
  }

}