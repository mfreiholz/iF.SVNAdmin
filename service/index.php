<?php
//error_reporting(E_ERROR);
// Define the relative or absolute path to the framework's base directory,
// before including the "autoload.php" from the directory.
define("HRF_AUTO_LOAD_BASE_DIR", "../../humble-rest-framework-php/src");
require_once(HRF_AUTO_LOAD_BASE_DIR . "/autoload.php");

define("SVNADMIN_BASE_DIR", realpath("../"));
require_once("../include/core/SVNAdminEngine.php");

// Create Engine with custom configuration.
// Copy from $HRF_BASE_DIR/config.dist.php
$e = new Engine((include '../config/services.php'));
$e->run();
?>