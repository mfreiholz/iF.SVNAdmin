<?php
// Configure error handling.
error_reporting(-1);
ini_set("log_errors", 1);
ini_set("error_log", realpath("../") . "/data/error.log");

function errorHandler($errno, $errstr, $errfile = "", $errline = 0, array $errcontext = null) {
  switch ($errno) {
    case E_USER_ERROR:
      break;
    case E_USER_WARNING:
      break;
    case E_USER_NOTICE:
      break;
    case E_USER_DEPRECATED:
      break;
    case E_ERROR:
      break;
    case E_WARNING:
      break;
    case E_NOTICE:
      break;
    case E_DEPRECATED:
      break;
  }
  error_log("[" . date(DATE_ATOM) . "][type=" . $errno. "][line=" . $errline . "][file=" . $errfile . "] " . $errstr);
  return false;
}
set_error_handler("errorHandler");

///////////////////////////////////////////////////////////////////////
// Application Code Starts Here
///////////////////////////////////////////////////////////////////////

// Define the relative or absolute path to the framework's base directory,
// before including the "autoload.php" from the directory.
define("HRF_AUTO_LOAD_BASE_DIR", "../../humble-rest-framework-php/src");
require_once(HRF_AUTO_LOAD_BASE_DIR . "/autoload.php");

define("SVNADMIN_BASE_DIR", realpath("../"));
define("SVNADMIN_DATA_DIR", realpath("../data"));
require_once("../include/core/SVNAdminEngine.php");

// Create Engine with custom configuration.
// Copy from $HRF_BASE_DIR/config.dist.php
$e = new Engine((include '../config/services.php'));
$e->run();
?>