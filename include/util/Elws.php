<?php
/**
 * The Elws class is used to provide all kind of utility functions.
 * (Elws => German: "Eierlegende Wollmilchsau")
 */
class Elws {

  public static function normalizeAbsolutePath($path) {
    if (empty($path)) {
      return $path;
    }
    $path = realpath($path);
    $path = str_replace("\\", "/", $path);
    return $path;
  }

}