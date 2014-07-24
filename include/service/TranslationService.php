<?php
class TranslationService extends ServiceBase {

  public function processRequest(WebRequest $request, WebResponse $response) {
    $action = $request->getParameter("action");
    switch ($action) {
      case "locales":
        return $this->processAvailableLocales($request, $response);
      case "translations":
        return $this->processTranslation($request, $response);
    }
    return false;
  }

  public function processAvailableLocales(WebRequest $request, WebResponse $response) {
    $config = include (SVNADMIN_BASE_DIR . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "translations.php");
    $response->done2json($config);
    return true;
  }

  public function processTranslation(WebRequest $request, WebResponse $response) {
    $locale = $request->getParameter("locale", "en_gb");
    $format = $request->getParameter("format", "json");

    $config = include (SVNADMIN_BASE_DIR . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "translations.php");
    if (!isset($config[$locale])) {
      $response->fail(500);
      $response->write2json(array (
          "message" => "Unknown locale"
      ));
      return true;
    }

    $translations = array ();
    if (isset($config[$locale]["csv_file"])) {
      $translations = $this->parseFromCsv($config[$locale]["csv_file"]);
    } else if (isset($config[$locale]["php_file"])) {
      $translations = include ($config[$locale]["php_file"]);
    }
    switch ($format) {
      case "json":
      default:
        $response->done2json($translations);
        return true;
    }
    return true;
  }

  private function parseFromCsv($filePath) {
    if (!file_exists($filePath)) {
      return array ();
    }
    // Read and parse file.
    $fh = fopen($filePath, "r");
    if ($fh === false) {
      return array ();
    }
    $arr = array ();
    $data = null;
    while (($data = fgetcsv($fh)) !== false) {
      if (count($data) !== 2) {
        continue;
      }
      $arr[$data[0]] = $data[1];
    }
    fclose($fh);
    return $arr;
  }

}
?>