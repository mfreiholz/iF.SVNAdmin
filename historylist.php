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
include("include/config.inc.php");
$appEngine->forwardInvalidModule(!$appEngine->isUserViewActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_HISTORY, ACL_ACTION_VIEW);
$appTR->loadModule("historylist");

// Get all histories list
$histories = $appEngine->getHistoryViewProvider()->getHistories();
global $appEngine;$appEngine->addMessage(var_dump($histories));
usort( $histories, array('\svnadmin\core\entities\History',"compare") );
SetValue("HistoryList", $histories);
ProcessTemplate("history/historylist.html.php");
?>