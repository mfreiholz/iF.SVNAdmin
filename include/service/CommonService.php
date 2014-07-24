<?php
class CommonService extends ServiceBase {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action");
    switch ($action) {
      case "systeminfo":
        return $this->processSystemInfo($request, $response);
      case "filesysteminfo":
        return $this->processFileSystemInfo($request, $response);
    }
    return false;
  }

  public function processSystemInfo(WebRequest $request, WebResponse $response) {
    $json = new stdClass();
    $json->os = php_uname("s");
    $json->osversion = php_uname("v");
    $json->machine = php_uname("m");
    $json->hostname = php_uname("n");
    $json->php = array (
        "versionname" => php_uname("r"),
        "memorypeakusage" => memory_get_peak_usage(),
        "memoryusage" => memory_get_usage(),
        "sapiname" => php_sapi_name()
    );
    $response->done2json($json);
    return true;
  }

  public function processFileSystemInfo(WebRequest $request, WebResponse $response) {
    $json = new stdClass();
    $json->diskfreespace = disk_free_space(".");
    $json->disktotalspace = disk_total_space(".");
    $response->done2json($json);
    return true;
  }

}