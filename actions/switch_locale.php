<?php
/**
 * iF.SVNAdmin
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
if( !defined('ACTION_HANDLING') ) {
  die("HaHa!");
}

$requestedLocale = get_request_var("locale");

// Set the new locale.
if ($requestedLocale == NULL)
{
  // No locale given...
}
else
{
  $_COOKIE["locale"] = $requestedLocale;
  @setcookie("locale", $requestedLocale, time()+60*60*24*365); // 365 days
  header("Location: index.php");
}
?>